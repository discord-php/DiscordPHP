<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\normalizePartId;
use function React\Promise\resolve;

/**
 * Represents a reaction to a message by members(s).
 *
 * @property string       $id         The identifier of the reaction.
 * @property int          $count      Number of reactions.
 * @property bool         $me         Whether the current bot has reacted.
 * @property Emoji        $emoji      The emoji that was reacted with.
 * @property string       $message_id The message ID the reaction is for.
 * @property Message|null $message    The message the reaction is for.
 * @property string       $channel_id The channel ID that the message belongs in.
 * @property Channel      $channel    The channel that the message belongs tol
 */
class Reaction extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['count', 'me', 'emoji', 'message_id', 'channel_id'];

    /**
     * @inheritdoc
     */
    public function isPartial(): bool
    {
        return $this->message == null;
    }

    /**
     * @inheritdoc
     */
    public function fetch(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->message_id))
            ->then(function ($message) {
                $this->attributes['message'] = $this->factory->create(Message::class, $message, true);

                return $this;
            });
    }

    /**
     * Gets the emoji identifier, combination of `id` and `name`.
     *
     * @return string
     */
    protected function getIdAttribute(): string
    {
        if ($this->emoji->id === null) {
            return $this->emoji->name;
        }

        return ":{$this->emoji->name}:{$this->emoji->id}";
    }

    /**
     * Gets the users that have used the reaction.
     *
     * @param array $options See https://discord.com/developers/docs/resources/channel#get-reactions
     *
     * @return ExtendedPromiseInterface<Collection|Users[]>
     */
    public function getUsers(array $options = []): ExtendedPromiseInterface
    {
        $query = Endpoint::bind(Endpoint::MESSAGE_REACTION_EMOJI, $this->channel_id, $this->message_id, urlencode($this->id));

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['before', 'after', 'limit'])
            ->setAllowedTypes('before', ['int', 'string', User::class])
            ->setAllowedTypes('after', ['int', 'string', User::class])
            ->setAllowedTypes('limit', 'int')
            ->setNormalizer('before', normalizePartId())
            ->setNormalizer('after', normalizePartId())
            ->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);

        foreach ($options as $key => $value) {
            $query->addQuery($key, $value);
        }

        return $this->http->get($query)
        ->then(function ($response) {
            $users = new Collection([], 'id', User::class);

            foreach ((array) $response as $user) {
                if ($part = $this->discord->users->get('id', $user->id)) {
                    $users->push($part);
                } else {
                    $users->push(new User($this->discord, (array) $user, true));
                }
            }

            return $users;
        });
    }

    /**
     * Gets all the users that have used this reaction.
     * Wrapper of the lower-level getUsers() function.
     *
     * @return ExtendedPromiseInterface<Collection|Users[]>
     */
    public function getAllUsers(): ExtendedPromiseInterface
    {
        $response = Collection::for(User::class);
        $getUsers = function ($after = null) use (&$getUsers, $response) {
            $options = ['limit' => 100];
            if ($after != null) {
                $options['after'] = $after;
            }

            return $this->getUsers($options)->then(function (Collection $users) use ($response, &$getUsers) {
                $last = null;
                foreach ($users as $user) {
                    $response->push($user);
                    $last = $user;
                }

                if ($users->count() < 100) {
                    return resolve($response);
                }

                return $getUsers($last);
            });
        };

        return $getUsers();
    }

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji
     * @throws \Exception
     */
    protected function getEmojiAttribute(): ?Part
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }

    /**
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            return $channel->messages->offsetGet($this->message_id);
        }

        return $this->attributes['message'] ?? null;
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel
     */
    protected function getChannelAttribute(): Channel
    {
        return $this->discord->getChannel($this->channel_id);
    }
}
