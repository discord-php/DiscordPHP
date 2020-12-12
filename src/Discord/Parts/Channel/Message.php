<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Role;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Repository\Channel\ReactionRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Partial\bind as Bind;

/**
 * A message which is posted to a Discord text channel.
 *
 * @property string                         $id               The unique identifier of the message.
 * @property Channel                        $channel          The channel that the message was sent in.
 * @property string                         $channel_id       The unique identifier of the channel that the message was went in.
 * @property string                         $content          The content of the message if it is a normal message.
 * @property int                            $type             The type of message.
 * @property Collection|User[]              $mentions         A collection of the users mentioned in the message.
 * @property Member|User                    $author           The author of the message.
 * @property string                         $user_id          The user id of the author.
 * @property bool                           $mention_everyone Whether the message contained an @everyone mention.
 * @property Carbon                         $timestamp        A timestamp of when the message was sent.
 * @property Carbon|null                    $edited_timestamp A timestamp of when the message was edited, or null.
 * @property bool                           $tts              Whether the message was sent as a text-to-speech message.
 * @property array                          $attachments      An array of attachment objects.
 * @property Collection|Embed[]             $embeds           A collection of embed objects.
 * @property string|null                    $nonce            A randomly generated string that provides verification for the client. Not required.
 * @property Collection|Role[]              $mention_roles    A collection of roles that were mentioned in the message.
 * @property bool                           $pinned           Whether the message is pinned to the channel.
 * @property Collection|Channel[]           $mention_channels Collection of mentioned channels.
 * @property ReactionRepository             $reactions        Collection of reactions on the message.
 * @property string                         $webhook_id       ID of the webhook that made the message, if any.
 * @property object                         $activity         Current message activity. Requires rich presence.
 * @property object                         $application      Application of message. Requires rich presence.
 * @property object                         $message_reference Message that is referenced by this message.
 * @property int                            $flags             Message flags.
 * @property bool                           $crossposted       Message has been crossposted.
 * @property bool                           $is_crosspost      Message is a crosspost from another channel.
 * @property bool                           $suppress_embeds   Do not include embeds when serializing message.
 * @property bool                           $source_message_deleted Source message for this message has been deleted.
 * @property bool                           $urgent            Message is urgent.
 */
class Message extends Part
{
    const TYPE_NORMAL = 0;
    const TYPE_USER_ADDED = 1;
    const TYPE_USER_REMOVED = 2;
    const TYPE_CALL = 3;
    const TYPE_CHANNEL_NAME_CHANGE = 4;
    const TYPE_CHANNEL_ICON_CHANGE = 5;
    const CHANNEL_PINNED_MESSAGE = 6;
    const GUILD_MEMBER_JOIN = 7;
    const USER_PREMIUM_GUILD_SUBSCRIPTION = 8;
    const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_1 = 9;
    const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_2 = 10;
    const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_3 = 11;
    const CHANNEL_FOLLOW_ADD = 12;
    const GUILD_DISCOVERY_DISQUALIFIED = 14;
    const GUILD_DISCOVERY_REQUALIFIED = 15;

    const ACTIVITY_JOIN = 1;
    const ACTIVITY_SPECTATE = 2;
    const ACTIVITY_LISTEN = 3;
    const ACTIVITY_JOIN_REQUEST = 4;

    const REACT_DELETE_ALL = 0;
    const REACT_DELETE_ME = 1;
    const REACT_DELETE_ID = 2;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'channel_id',
        'content',
        'type',
        'mentions',
        'author',
        'mention_everyone',
        'timestamp',
        'edited_timestamp',
        'tts',
        'attachments',
        'embeds',
        'nonce',
        'mention_roles',
        'pinned',
        'mention_channels',
        'reactions',
        'webhook_id',
        'activity',
        'application',
        'message_reference',
        'flags',
    ];

    /**
     * {@inheritdoc}
     */
    protected $repositories = [
        'reactions' => ReactionRepository::class,
    ];

    /**
     * Gets the crossposted attribute.
     *
     * @return bool
     */
    protected function getCrosspostedAttribute(): bool
    {
        return (bool) ($this->flags & (1 << 0));
    }

    /**
     * Gets the is_crosspost attribute.
     *
     * @return bool
     */
    protected function getIsCrosspostAttribute(): bool
    {
        return (bool) ($this->flags & (1 << 1));
    }

    /**
     * Gets the suppress_embeds attribute.
     *
     * @return bool
     */
    protected function getSuppressEmbedsAttribute(): bool
    {
        return (bool) ($this->flags & (1 << 2));
    }

    /**
     * Gets the source_message_deleted attribute.
     *
     * @return bool
     */
    protected function getSourceMessageDeletedAttribute(): bool
    {
        return (bool) ($this->flags & (1 << 3));
    }

    /**
     * Gets the urgent attribute.
     *
     * @return bool
     */
    protected function getUrgentAttribute(): bool
    {
        return (bool) ($this->flags & (1 << 4));
    }

    /**
     * Gets the mention_channels attribute.
     *
     * @return Collection|Channel[]
     * @throws \Exception
     */
    protected function getMentionChannelsAttribute(): Collection
    {
        $collection = new Collection([], 'id', Channel::class);

        if (preg_match_all('/<#([0-9]*)>/', $this->content, $matches)) {
            foreach ($matches[1] as $channelId) {
                if ($channel = $this->discord->getChannel($channelId)) {
                    $collection->push($channel);
                }
            }
        }

        foreach ($this->attributes['mention_channels'] ?? [] as $mention_channel) {
            if (! $channel = $this->discord->getChannel($mention_channel->id)) {
                $channel = $this->factory->create(Channel::class, $mention_channel, true);
            }

            $collection->push($channel);
        }

        return $collection;
    }

    /**
     * Sets the reactions attriubte.
     *
     * @param iterable $reactions
     */
    protected function setReactionsAttribute(iterable $reactions)
    {
        $this->reactions->clear();

        foreach ($reactions as $reaction) {
            $this->reactions->push($this->reactions->create((array) $reaction, true));
        }
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel    The channel the message was sent in.
     * @throws \Exception
     */
    protected function getChannelAttribute(): Channel
    {
        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return $this->factory->create(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the mention_roles attribute.
     *
     * @return Collection The roles that were mentioned.
     */
    protected function getMentionRolesAttribute(): Collection
    {
        $roles = new Collection();

        if ($this->channel->guild) {
            foreach ($this->channel->guild->roles ?? [] as $role) {
                if (array_search($role->id, $this->attributes['mention_roles'] ?? []) !== false) {
                    $roles->push($role);
                }
            }
        }

        return $roles;
    }

    /**
     * Returns the mention attribute.
     *
     * @return Collection The users that were mentioned.
     * @throws \Exception
     */
    protected function getMentionsAttribute(): Collection
    {
        $users = new Collection();

        foreach ($this->attributes['mentions'] ?? [] as $mention) {
            if (! $user = $this->discord->users->get('id', $mention->id)) {
                $user = $this->factory->create(User::class, $mention, true);
            }
            $users->push($user);
        }

        return $users;
    }

    /**
     * Returns the `user_id` attribute.
     *
     * @return string
     */
    protected function getUserIdAttribute(): ?string
    {
        return $this->attributes['author']->id ?? null;
    }

    /**
     * Returns the author attribute.
     *
     * @return User|Member The member that sent the message. Will return a User object if it is a PM.
     * @throws \Exception
     */
    protected function getAuthorAttribute(): ?Part
    {
        if (! isset($this->attributes['author'])) {
            return null;
        }

        if (($this->channel->guild &&
            $author = $this->channel->guild->members->get('id', $this->attributes['author']->id)) ||
            $author = $this->discord->users->get('id', $this->attributes['author']->id)
        ) {
            return $author;
        }

        return $this->factory->create(User::class, $this->attributes['author'], true);
    }

    /**
     * Returns the embed attribute.
     *
     * @return Collection A collection of embeds.
     * @throws \Exception
     */
    protected function getEmbedsAttribute(): Collection
    {
        $embeds = new Collection([], null);

        foreach ($this->attributes['embeds'] ?? [] as $embed) {
            $embeds->push($this->factory->create(Embed::class, $embed, true));
        }

        return $embeds;
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon     The time that the message was sent.
     * @throws \Exception
     */
    protected function getTimestampAttribute(): ?Carbon
    {
        if ($this->attributes['timestamp'] ?? null) {
            return new Carbon($this->attributes['timestamp']);
        }

        return null;
    }

    /**
     * Returns the edited_timestamp attribute.
     *
     * @return Carbon|null The time that the message was edited.
     * @throws \Exception
     */
    protected function getEditedTimestampAttribute(): ?Carbon
    {
        if ($this->attributes['edited_timestamp'] ?? null) {
            return new Carbon($this->attributes['edited_timestamp']);
        }

        return null;
    }

    /**
     * Replies to the message.
     *
     * @param string $text The text to reply with.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function reply(string $text): ExtendedPromiseInterface
    {
        return $this->channel->sendMessage("{$this->author}, {$text}");
    }

    /**
     * Crossposts the message to any following channels.
     *
     * @return ExtendedPromiseInterface
     */
    public function crosspost(): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $this->http->post("channels/{$this->channel_id}/messages/{$this->id}/crosspost")->done(function ($response) use ($deferred) {
            $message = $this->factory->part(Message::class, $response, true);
            $deferred->resolve($message);
        }, Bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * Send message after delay.
     *
     * @param string $text  Text to send after delay.
     * @param int    $delay Delay after text will be sent in milliseconds.
     *
     * @return ExtendedPromiseInterface
     */
    public function delayedReply(string $text, int $delay): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $this->discord->getLoop()->addTimer($delay / 1000, function () use ($text, $deferred) {
            $this->reply($text)->done(
                Bind([$deferred, 'resolve']),
                Bind([$deferred, 'reject'])
            );
        });

        return $deferred->promise();
    }

    /**
     * Reacts to the message.
     *
     * @param Emoji|string $emoticon The emoticon to react with. (custom: ':michael:251127796439449631')
     *
     * @return ExtendedPromiseInterface
     */
    public function react($emoticon): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        }

        $emoticon = urlencode($emoticon);

        $this->http->put(
            "channels/{$this->channel->id}/messages/{$this->id}/reactions/{$emoticon}/@me"
        )->done(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Deletes a reaction.
     *
     * @param int               $type     The type of deletion to perform.
     * @param Emoji|string|null $emoticon The emoticon to delete (if not all).
     * @param string|null       $id       The user reaction to delete (if not all).
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteReaction(int $type, $emoticon = null, ?string $id = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $types = [self::REACT_DELETE_ALL, self::REACT_DELETE_ME, self::REACT_DELETE_ID];

        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        }

        if (in_array($type, $types)) {
            switch ($type) {
                case self::REACT_DELETE_ALL:
                    $url = "channels/{$this->channel->id}/messages/{$this->id}/reactions";
                    break;
                case self::REACT_DELETE_ME:
                    $url = "channels/{$this->channel->id}/messages/{$this->id}/reactions/{$emoticon}/@me";
                    break;
                case self::REACT_DELETE_ID:
                    $url = "channels/{$this->channel->id}/messages/{$this->id}/reactions/{$emoticon}/{$id}";
                    break;
            }

            $this->http->delete(
                $url, []
            )->done(
                Bind([$deferred, 'resolve']),
                Bind([$deferred, 'reject'])
            );
        } else {
            $deferred->reject();
        }

        return $deferred->promise();
    }

    /**
     * Deletes the message from the channel.
     *
     * @return ExtendedPromiseInterface
     */
    public function delete(): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $this->http->delete("channels/{$this->channel_id}/messages/{$this->id}")->done(
            Bind([$deferred, 'resolve']),
            Bind([$deferred, 'reject'])
        );

        return $deferred->promise();
    }

    /**
     * Creates a reaction collector for the message.
     *
     * @param callable $filter           The filter function. Returns true or false.
     * @param int      $options['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options['limit'] The amount of reactions allowed or false.
     *
     * @return ExtendedPromiseInterface
     */
    public function createReactionCollector(callable $filter, array $options = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $reactions = new Collection([], null, null);
        $timer = null;

        $options = array_merge([
            'time' => false,
            'limit' => false,
        ], $options);

        $eventHandler = function (MessageReaction $reaction) use (&$eventHandler, $filter, $options, &$reactions, &$deferred, &$timer) {
            if ($reaction->message_id != $this->id) {
                return;
            }

            $filterResult = call_user_func_array($filter, [$reaction]);

            if ($filterResult) {
                $reactions->push($reaction);

                if ($options['limit'] !== false && sizeof($reactions) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_REACTION_ADD, $eventHandler);
                    $deferred->resolve($reactions);

                    if (! is_null($timer)) {
                        $this->discord->getLoop()->cancelTimer($timer);
                    }
                }
            }
        };
        $this->discord->on(Event::MESSAGE_REACTION_ADD, $eventHandler);

        if ($options['time'] !== false) {
            $timer = $this->discord->getLoop()->addTimer($options['time'] / 1000, function () use (&$eventHandler, &$reactions, &$deferred) {
                $this->discord->removeListener(Event::MESSAGE_REACTION_ADD, $eventHandler);
                $deferred->resolve($reactions);
            });
        }

        return $deferred->promise();
    }

    /**
     * Adds an embed to the message.
     *
     * @param Embed $embed
     *
     * @return ExtendedPromiseInterface
     */
    public function addEmbed(Embed $embed): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $this->http->patch("channels/{$this->channel_id}/messages/{$this->id}", [
            'embed' => $embed->getRawAttributes(),
        ])->done(function ($data) use ($deferred) {
            $this->fill((array) $data);
            $deferred->resolve($this);
        }, Bind([$deferred, 'reject']));

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'content' => $this->content,
            'flags' => $this->flags,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'message_id' => $this->id,
            'channel_id' => $this->channel_id,
        ];
    }
}
