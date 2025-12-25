<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\Thread\Thread;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;
use stdClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\normalizePartId;
use function React\Promise\resolve;

/**
 * Represents a reaction emoji to a message by members(s).
 *
 * @link https://discord.com/developers/docs/resources/message#reaction-object
 *
 * @since 5.0.0
 *
 * @property int                  $count         Total number of times this emoji has been used to react (including super reacts).
 * @property ReactionCountDetails $count_details Reaction count details object.
 * @property bool                 $me            Whether the current user reacted using this emoji.
 * @property bool                 $me_burst      Whether the current user super-reacted using this emoji.
 * @property Emoji                $emoji         Emoji information.
 * @property array                $burst_colors  HEX colors used for super reaction.
 *
 * @property      string         $channel_id The channel ID that the message belongs in.
 * @property-read Channel|Thread $channel    The channel that the message belongs to.
 * @property      string         $message_id The message ID the reaction is for.
 * @property      Message|null   $message    The message the reaction is for.
 * @property      string|null    $guild_id   The guild ID of the guild that owns the channel the message belongs in.
 * @property-read Guild|null     $guild      The guild that owns the channel the message belongs in.
 *
 * @property-read string $id The identifier of the reaction.
 */
class Reaction extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id', // internal
        'count',
        'count_details',
        'me',
        'me_burst',
        'emoji',
        'burst_colors',

        // events only
        'channel_id',
        'message_id',
        'guild_id',
    ];

    /**
     * @inheritDoc
     */
    public function isPartial(): bool
    {
        return $this->message === null;
    }

    /**
     * @inheritDoc
     */
    public function fetch(): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->message_id))
            ->then(function ($message) {
                $this->message = $this->factory->part(Message::class, (array) $message + ['guild_id' => $this->guild_id], true);

                return $this;
            });
    }

    /**
     * Sets the emoji identifier.
     *
     * @internal Used for ReactionRepository::fetch()
     *
     * @param string $value name:id or the character of standard emoji
     *
     *
     * @since 10.0.0
     */
    protected function setIdAttribute(string $value): void
    {
        if (! isset($this->attributes['emoji'])) {
            $this->attributes['emoji'] = new stdClass();
        }

        $colonDelimiter = explode(':', $value);
        $delimitedCount = count($colonDelimiter);
        $emojiId = $emojiAnimated = null;

        if ($delimitedCount === 2) { // Custom emoji name:id
            [$emojiName, $emojiId] = $colonDelimiter;
        } elseif ($delimitedCount === 3) { // Custom animated emoji a:name:id
            [$emojiAnimated, $emojiName, $emojiId] = $colonDelimiter;
        } else { // Standard emoji (or just have abnormal colon count)
            $emojiName = $value;
        }

        $this->attributes['emoji']->id = $emojiId;
        $this->attributes['emoji']->name = $emojiName;
        if ($emojiAnimated === 'a') {
            $this->attributes['emoji']->animated = true;
        }
    }

    /**
     * Gets the emoji identifier.
     *
     * @return string
     *
     * @since 10.0.0 Changed to only return custom emoji id or unicode emoji name.
     */
    protected function getIdAttribute(): string
    {
        return $this->emoji->id ?? $this->emoji->name;
    }

    /**
     * Gets the users that have used the reaction.
     *
     * @param array       $options          An array of options. All fields are optional.
     * @param string|null $options['after'] Get users after this user ID.
     * @param int|null    $options['limit'] Max number of users to return (1-100).
     *
     * @link https://discord.com/developers/docs/resources/channel#get-reactions
     *
     * @return PromiseInterface<ExCollectionInterface<User>|User[]>
     */
    public function getUsers(array $options = []): PromiseInterface
    {
        $query = Endpoint::bind(Endpoint::MESSAGE_REACTION_EMOJI, $this->channel_id, $this->message_id, urlencode($this->emoji->id === null ? $this->emoji->name : "{$this->emoji->name}:{$this->emoji->id}"));

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['after', 'limit'])
            ->setAllowedTypes('after', ['int', 'string', User::class])
            ->setAllowedTypes('limit', 'int')
            ->setNormalizer('after', normalizePartId())
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);

        foreach ($options as $key => $value) {
            $query->addQuery($key, $value);
        }

        return $this->http->get($query)
        ->then(function ($response) {
            /** @var ExCollectionInterface<User> $users */
            $users = $this->discord->getCollectionClass()::for(User::class);

            foreach ((array) $response as $user) {
                if (! $part = $this->discord->users->get('id', $user->id)) {
                    $part = $this->discord->users->create($user, true);
                    $this->discord->users->pushItem($part);
                }

                $users->pushItem($part);
            }

            return $users;
        });
    }

    /**
     * Gets all the users that have used this reaction.
     * Wrapper of the lower-level getUsers() function.
     *
     * @see Message::getUsers()
     *
     * @return PromiseInterface<ExCollectionInterface<User>|User[]>
     */
    public function getAllUsers(): PromiseInterface
    {
        return $this->__getUsers($this->discord->getCollectionClass()::for(User::class));
    }

    /**
     * Recursively retrieves users who reacted, handling pagination.
     *
     * @param ExCollectionInterface $response The collection to accumulate users into.
     * @param mixed|null            $after    The user ID to paginate after, or null to start from the beginning.
     *
     * @return PromiseInterface<ExCollectionInterface<User>|User[]> Resolves with the collection of users who reacted.
     */
    protected function __getUsers(ExCollectionInterface $response, $after = null): PromiseInterface
    {
        $options = ['limit' => 100];
        if ($after !== null) {
            $options['after'] = $after;
        }

        return $this->getUsers($options)->then(function (ExCollectionInterface $users) use ($response) {
            $last = null;
            foreach ($users as $user) {
                $response->pushItem($user);
                $last = $user;
            }

            if ($users->count() < 100) {
                return resolve($response);
            }

            return $this->__getUsers($response, $last);
        });
    }

    /**
     * Gets the count details attribute.
     *
     * @return ReactionCountDetails
     */
    protected function getCountDetailsAttribute(): ReactionCountDetails
    {
        return $this->attributePartHelper('count_details', ReactionCountDetails::class);
    }

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        return $this->attributePartHelper('emoji', Emoji::class, ['guild_id' => $this->guild_id]);
    }

    /**
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->get('id', $this->message_id);
        }

        return $this->attributePartHelper('message', Message::class);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|Thread
     */
    protected function getChannelAttribute(): Part
    {
        if ($guild = $this->guild) {
            $channels = $guild->channels;
            if ($channel = $channels->get('id', $this->channel_id)) {
                return $channel;
            }
            foreach ($channels as $parent) {
                if ($thread = $parent->threads->get('id', $this->channel_id)) {
                    return $thread;
                }
            }
        }

        if ($channel = $this->discord->private_channels->get('id', $this->channel_id)) {
            return $channel;
        }

        return $this->factory->part(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the guild that owns the channel the message was sent in.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->guild_id)) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'emoji' => isset($this->attributes['emoji']->id) ? $this->attributes['emoji']->name.':'.$this->attributes['emoji']->id : urlencode($this->attributes['emoji']->name),
        ];
    }
}
