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

use Carbon\Carbon;
use Discord\Builders\MessageBuilder;
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
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\Interactions\Request\Component;
use Discord\Parts\Thread\Thread;
use Discord\Parts\WebSockets\MessageInteraction;
use Discord\Repository\Channel\ReactionRepository;
use React\EventLoop\TimerInterface;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function React\Promise\reject;

/**
 * A message which is posted to a Discord text channel.
 *
 * @link https://discord.com/developers/docs/resources/channel#message-object
 *
 * @since 2.0.0
 *
 * @property      string                      $id                     The unique identifier of the message.
 * @property      string                      $channel_id             The unique identifier of the channel that the message was went in.
 * @property-read Channel|Thread              $channel                The channel that the message was sent in.
 * @property      User|null                   $author                 The author of the message. Will be a webhook if sent from one.
 * @property-read string|null                 $user_id                The user id of the author.
 * @property      string                      $content                The content of the message if it is a normal message.
 * @property      Carbon                      $timestamp              A timestamp of when the message was sent.
 * @property      Carbon|null                 $edited_timestamp       A timestamp of when the message was edited, or null.
 * @property      bool                        $tts                    Whether the message was sent as a text-to-speech message.
 * @property      bool                        $mention_everyone       Whether the message contained an @everyone mention.
 * @property      Collection|User[]           $mentions               A collection of the users mentioned in the message.
 * @property      Collection|Role[]           $mention_roles          A collection of roles that were mentioned in the message.
 * @property      Collection|Channel[]        $mention_channels       Collection of mentioned channels.
 * @property      Collection|Attachment[]     $attachments            Collection of attachment objects.
 * @property      Collection|Embed[]          $embeds                 A collection of embed objects.
 * @property      ReactionRepository          $reactions              Collection of reactions on the message.
 * @property      string|null                 $nonce                  A randomly generated string that provides verification for the client. Not required.
 * @property      bool                        $pinned                 Whether the message is pinned to the channel.
 * @property      string|null                 $webhook_id             ID of the webhook that made the message, if any.
 * @property      int                         $type                   The type of message.
 * @property      object|null                 $activity               Current message activity. Requires rich presence.
 * @property      object|null                 $application            Application of message. Requires rich presence.
 * @property      string|null                 $application_id         If the message is a response to an Interaction, this is the id of the interaction's application.
 * @property      object|null                 $message_reference      Message that is referenced by this message.
 * @property      int|null                    $flags                  Message flags.
 * @property      Message|null                $referenced_message     The message that is referenced in a reply.
 * @property      MessageInteraction|null     $interaction            Sent if the message is a response to an Interaction.
 * @property      Thread|null                 $thread                 The thread that was started from this message, includes thread member object.
 * @property      Collection|Component[]|null $components             Sent if the message contains components like buttons, action rows, or other interactive components.
 * @property      Collection|Sticker[]|null   $sticker_items          Stickers attached to the message.
 * @property      int|null                    $position               A generally increasing integer (there may be gaps or duplicates) that represents the approximate position of the message in a thread, it can be used to estimate the relative position of the message in a thread in company with `total_message_sent` on parent thread.
 * @property      object|null                 $role_subscription_data Data of the role subscription purchase or renewal that prompted this `ROLE_SUBSCRIPTION_PURCHASE` message.
 *
 * @property-read bool $crossposted                            Message has been crossposted.
 * @property-read bool $is_crosspost                           Message is a crosspost from another channel.
 * @property-read bool $suppress_embeds                        Do not include embeds when serializing message.
 * @property-read bool $source_message_deleted                 Source message for this message has been deleted.
 * @property-read bool $urgent                                 Message is urgent.
 * @property-read bool $has_thread                             Whether this message has an associated thread, with the same id as the message.
 * @property-read bool $ephemeral                              Whether this message is only visible to the user who invoked the Interaction.
 * @property-read bool $loading                                Whether this message is an Interaction Response and the bot is "thinking".
 * @property-read bool $failed_to_mention_some_roles_in_thread This message failed to mention some roles and add their members to the thread.
 * @property-read bool $suppress_notifications                 This message will not trigger push and desktop notifications.
 *
 * @property      string|null $guild_id The unique identifier of the guild that the channel the message was sent in belongs to.
 * @property-read Guild|null  $guild    The guild that the message was sent in.
 * @property      Member|null $member   The member that sent this message, or null if it was in a private message.
 * @property-read string|null $link     Returns a link to the message.
 */
class Message extends Part
{
    public const TYPE_DEFAULT = 0;
    public const TYPE_RECIPIENT_ADD = 1;
    public const TYPE_RECIPIENT_REMOVE = 2;
    public const TYPE_CALL = 3;
    public const TYPE_CHANNEL_NAME_CHANGE = 4;
    public const TYPE_CHANNEL_ICON_CHANGE = 5;
    public const TYPE_CHANNEL_PINNED_MESSAGE = 6;
    public const TYPE_USER_JOIN = 7;
    public const TYPE_GUILD_BOOST = 8;
    public const TYPE_GUILD_BOOST_TIER_1 = 9;
    public const TYPE_GUILD_BOOST_TIER_2 = 10;
    public const TYPE_GUILD_BOOST_TIER_3 = 11;
    public const TYPE_CHANNEL_FOLLOW_ADD = 12;
    public const TYPE_GUILD_DISCOVERY_DISQUALIFIED = 14;
    public const TYPE_GUILD_DISCOVERY_REQUALIFIED = 15;
    public const TYPE_GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING = 16;
    public const TYPE_GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING = 17;
    public const TYPE_THREAD_CREATED = 18;
    public const TYPE_REPLY = 19;
    public const TYPE_CHAT_INPUT_COMMAND = 20;
    public const TYPE_THREAD_STARTER_MESSAGE = 21;
    public const TYPE_GUILD_INVITE_REMINDER = 22;
    public const TYPE_CONTEXT_MENU_COMMAND = 23;
    public const TYPE_AUTO_MODERATION_ACTION = 24;
    public const TYPE_ROLE_SUBSCRIPTION_PURCHASE = 25;
    public const TYPE_INTERACTION_PREMIUM_UPSELL = 26;
    public const TYPE_STAGE_START = 27;
    public const TYPE_STAGE_END = 28;
    public const TYPE_STAGE_SPEAKER = 29;
    public const TYPE_STAGE_TOPIC = 31;
    public const TYPE_GUILD_APPLICATION_PREMIUM_SUBSCRIPTION = 32;

    /** @deprecated 7.1.0 Use `Message::TYPE_USER_JOIN` */
    public const GUILD_MEMBER_JOIN = self::TYPE_USER_JOIN;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION = self::TYPE_GUILD_BOOST;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_1` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_1 = self::TYPE_GUILD_BOOST_TIER_1;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_2` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_2 = self::TYPE_GUILD_BOOST_TIER_2;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_3` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_3 = self::TYPE_GUILD_BOOST_TIER_3;
    /** @deprecated 10.0.0 Use `Message::TYPE_DEFAULT` */
    public const TYPE_NORMAL = self::TYPE_DEFAULT;
    /** @deprecated 10.0.0 Use `Message::TYPE_RECIPIENT_ADD` */
    public const TYPE_USER_ADDED = self::TYPE_RECIPIENT_ADD;
    /** @deprecated 10.0.0 Use `Message::TYPE_RECIPIENT_REMOVE` */
    public const TYPE_USER_REMOVED = self::TYPE_RECIPIENT_REMOVE;
    /** @deprecated 10.0.0 Use `Message::TYPE_CHANNEL_PINNED_MESSAGE` */
    public const CHANNEL_PINNED_MESSAGE = self::TYPE_CHANNEL_PINNED_MESSAGE;
    /** @deprecated 10.0.0 Use `Message::TYPE_CHANNEL_FOLLOW_ADD` */
    public const CHANNEL_FOLLOW_ADD = self::TYPE_CHANNEL_FOLLOW_ADD;
    /** @deprecated 10.0.0 Use `Message::TYPE_GUILD_DISCOVERY_DISQUALIFIED` */
    public const GUILD_DISCOVERY_DISQUALIFIED = self::TYPE_GUILD_DISCOVERY_DISQUALIFIED;
    /** @deprecated 10.0.0 Use `Message::TYPE_GUILD_DISCOVERY_REQUALIFIED` */
    public const GUILD_DISCOVERY_REQUALIFIED = self::TYPE_GUILD_DISCOVERY_REQUALIFIED;
    /** @deprecated 10.0.0 Use `Message::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING` */
    public const GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING = self::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING;
    /** @deprecated 10.0.0 Use `Message::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING` */
    public const GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING = self::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING;
    /** @deprecated 10.0.0 Use `Message::TYPE_CHAT_INPUT_COMMAND` */
    public const TYPE_APPLICATION_COMMAND = self::TYPE_CHAT_INPUT_COMMAND;

    public const ACTIVITY_JOIN = 1;
    public const ACTIVITY_SPECTATE = 2;
    public const ACTIVITY_LISTEN = 3;
    public const ACTIVITY_JOIN_REQUEST = 5;

    public const REACT_DELETE_ALL = 0;
    public const REACT_DELETE_ME = 1;
    public const REACT_DELETE_ID = 2;
    public const REACT_DELETE_EMOJI = 3;

    public const FLAG_CROSSPOSTED = (1 << 0);
    public const FLAG_IS_CROSSPOST = (1 << 1);
    public const FLAG_SUPPRESS_EMBED = (1 << 2);
    public const FLAG_SOURCE_MESSAGE_DELETED = (1 << 3);
    public const FLAG_URGENT = (1 << 4);
    public const FLAG_HAS_THREAD = (1 << 5);
    public const FLAG_EPHEMERAL = (1 << 6);
    public const FLAG_LOADING = (1 << 7);
    public const FLAG_FAILED_TO_MENTION_SOME_ROLES_IN_THREAD = (1 << 8);
    public const FLAG_SUPPRESS_NOTIFICATIONS = (1 << 12);

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'channel_id',
        'author',
        'content',
        'timestamp',
        'edited_timestamp',
        'tts',
        'mention_everyone',
        'mentions',
        'mention_roles',
        'mention_channels',
        'attachments',
        'embeds',
        'nonce',
        'pinned',
        'webhook_id',
        'type',
        'activity',
        'application',
        'application_id',
        'message_reference',
        'flags',
        'referenced_message',
        'interaction',
        'thread',
        'components',
        'sticker_items',
        'position',
        'role_subscription_data',

        // @internal
        'guild_id',
        'member',

        // repositories
        'reactions',
    ];

    /**
     * {@inheritDoc}
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
        return (bool) ($this->flags & self::FLAG_CROSSPOSTED);
    }

    /**
     * Gets the is_crosspost attribute.
     *
     * @return bool
     */
    protected function getIsCrosspostAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_IS_CROSSPOST);
    }

    /**
     * Gets the suppress_embeds attribute.
     *
     * @return bool
     */
    protected function getSuppressEmbedsAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_SUPPRESS_EMBED);
    }

    /**
     * Gets the source_message_deleted attribute.
     *
     * @return bool
     */
    protected function getSourceMessageDeletedAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_SOURCE_MESSAGE_DELETED);
    }

    /**
     * Gets the urgent attribute.
     *
     * @return bool
     */
    protected function getUrgentAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_URGENT);
    }

    /**
     * Gets the has thread attribute.
     *
     * @return bool
     */
    protected function getHasThreadAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_HAS_THREAD);
    }

    /**
     * Gets the ephemeral attribute.
     *
     * @return bool
     */
    protected function getEphemeralAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_EPHEMERAL);
    }

    /**
     * Gets the loading attribute.
     *
     * @return bool
     */
    protected function getLoadingAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_LOADING);
    }

    /**
     * Gets the failed to mention some roles in thread attribute.
     *
     * @return bool
     */
    protected function getFailedToMentionSomeRolesInThreadAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_FAILED_TO_MENTION_SOME_ROLES_IN_THREAD);
    }

    /**
     * Gets the suppress notification attribute.
     *
     * @return bool
     */
    protected function getSuppressNotificationsAttribute(): bool
    {
        return (bool) ($this->flags & self::FLAG_SUPPRESS_NOTIFICATIONS);
    }

    /**
     * Gets the mention_channels attribute.
     *
     * @return Collection|Channel[]
     */
    protected function getMentionChannelsAttribute(): Collection
    {
        $collection = Collection::for(Channel::class);

        if (preg_match_all('/<#([0-9]*)>/', $this->content, $matches)) {
            foreach ($matches[1] as $channelId) {
                if ($channel = $this->discord->getChannel($channelId)) {
                    $collection->pushItem($channel);
                }
            }
        }

        foreach ($this->attributes['mention_channels'] ?? [] as $mention_channel) {
            $collection->pushItem($this->discord->getChannel($mention_channel->id) ?: $this->factory->part(Channel::class, (array) $mention_channel, true));
        }

        return $collection;
    }

    /**
     * Returns any attached files.
     *
     * @return Collection|Attachment[] Attachment objects.
     */
    protected function getAttachmentsAttribute(): Collection
    {
        $attachments = Collection::for(Attachment::class);

        foreach ($this->attributes['attachments'] ?? [] as $attachment) {
            $attachments->pushItem($this->createOf(Attachment::class, $attachment));
        }

        return $attachments;
    }

    /**
     * Sets the reactions attribute.
     *
     * @param ?array $reactions
     */
    protected function setReactionsAttribute(?array $reactions): void
    {
        $keepReactions = [];
        foreach ($reactions ?? [] as $reaction) {
            $keepReactions[] = $reactionKey = $reaction->emoji->id ?? $reaction->emoji->name;
            $reaction = (array) $reaction;
            /** @var ?Reaction */
            if ($reactionPart = $this->reactions->offsetGet($reactionKey)) {
                $reactionPart->fill($reaction);
            } else {
                /** @var Reaction */
                $reactionPart = $this->reactions->create($reaction, $this->created);
                $reactionPart->created = &$this->created;
            }
            $this->reactions->pushItem($reactionPart);
        }

        $oldReactions = [];
        if (! empty($this->attributes['reactions'])) {
            foreach ($this->attributes['reactions'] as $oldReaction) {
                $oldReactions[] = $oldReaction->emoji->id ?? $oldReaction->emoji->name;
            }
            if ($clean = array_diff($oldReactions, $keepReactions)) {
                $this->reactions->cache->deleteMultiple($clean);
            }
        }

        $this->attributes['reactions'] = $reactions;
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|Thread The channel or thread the message was sent in.
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

        // @todo potentially slow
        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return $this->factory->part(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the thread that was started from this message, includes thread member object.
     *
     * @since 10.0.0 This only returns a thread that was started on this message, not the thread of the message.
     * @since 7.0.0
     *
     * @return Thread|null
     */
    protected function getThreadAttribute(): ?Thread
    {
        if (! isset($this->attributes['thread'])) {
            return null;
        }

        $thread = null;
        if ($guild = $this->guild) {
            if ($channel = $guild->channels->get('id', $this->attributes['thread']->parent_id)) {
                if ($thread = $channel->threads->get('id', $this->channel_id)) {
                    return $thread;
                }
                $thread = $channel->threads->create($this->attributes['thread'], $channel->created);
                $thread->created = &$channel->created;
                $channel->threads->pushItem($thread);
            }
        }

        return $thread;
    }

    /**
     * Returns the guild which the channel that the message was sent in belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
            return $guild;
        }

        // Workaround for Channel::sendMessage() no guild_id
        if ($this->channel_id) {
            return $this->discord->guilds->find(function (Guild $guild) {
                return $guild->channels->offsetExists($this->channel_id);
            });
        }

        return null;
    }

    /**
     * Returns the mention_roles attribute.
     *
     * @return Collection<?Role> The roles that were mentioned. null role only contains the ID in the collection.
     */
    protected function getMentionRolesAttribute(): Collection
    {
        $roles = new Collection();

        if (empty($this->attributes['mention_roles'])) {
            return $roles;
        }

        $roles->fill(array_fill_keys($this->attributes['mention_roles'], null));

        if ($guild = $this->guild) {
            $roles->merge($guild->roles->filter(function ($role) {
                return in_array($role->id, $this->attributes['mention_roles']);
            }));
        }

        return $roles;
    }

    /**
     * Returns the mention attribute.
     *
     * @return Collection|User[] The users that were mentioned.
     */
    protected function getMentionsAttribute(): Collection
    {
        $users = Collection::for(User::class);

        foreach ($this->attributes['mentions'] ?? [] as $mention) {
            $users->pushItem($this->discord->users->get('id', $mention->id) ?: $this->factory->part(User::class, (array) $mention, true));
        }

        return $users;
    }

    /**
     * Returns the `user_id` attribute.
     *
     * @return string|null
     */
    protected function getUserIdAttribute(): ?string
    {
        return $this->attributes['author']->id ?? null;
    }

    /**
     * Returns the author attribute.
     *
     * @return User|null The author of the message.
     */
    protected function getAuthorAttribute(): ?User
    {
        if (! isset($this->attributes['author'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['author']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['author'], true);
    }

    /**
     * Returns the member attribute.
     *
     * @return Member|null The member that sent the message, or null if it was in a private message.
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->attributes['author']->id)) {
                return $member;
            }
        }

        if (isset($this->attributes['member'])) {
            return $this->factory->part(Member::class, array_merge((array) $this->attributes['member'], [
                'user' => $this->attributes['author'],
                'guild_id' => $this->guild_id,
            ]), true);
        }

        return null;
    }

    /**
     * Returns the embed attribute.
     *
     * @return Collection<Embed> A collection of embeds.
     */
    protected function getEmbedsAttribute(): Collection
    {
        $embeds = new Collection([], null);

        foreach ($this->attributes['embeds'] ?? [] as $embed) {
            $embeds->pushItem($this->createOf(Embed::class, $embed));
        }

        return $embeds;
    }

    /**
     * Gets the interaction which triggered the message (application commands).
     *
     * @return MessageInteraction|null
     */
    protected function getInteractionAttribute(): ?MessageInteraction
    {
        if (! isset($this->attributes['interaction'])) {
            return null;
        }

        return $this->createOf(MessageInteraction::class, (array) $this->attributes['interaction'] + ['guild_id' => $this->guild_id]);
    }

    /**
     * Gets the referenced message attribute, if present.
     *
     * @return Message|null
     */
    protected function getReferencedMessageAttribute(): ?Message
    {
        // try get the message from the relevant repository
        // otherwise, if message is present in payload, create it
        // otherwise, return null
        if ($reference = $this->attributes['message_reference'] ?? null) {
            if (isset($reference->message_id, $reference->channel_id)) {
                $channel = null;

                if (isset($reference->guild_id) && $guild = $this->discord->guilds->get('id', $reference->guild_id)) {
                    $channel = $guild->channels->get('id', $reference->channel_id);
                }

                // @todo potentially slow
                if (! $channel && ! isset($this->attributes['referenced_message'])) {
                    $channel = $this->discord->getChannel($reference->channel_id);
                }

                if ($channel) {
                    if ($message = $channel->messages->get('id', $reference->message_id)) {
                        return $message;
                    }
                }
            }
        }

        if (isset($this->attributes['referenced_message'])) {
            return $this->factory->part(Message::class, (array) $this->attributes['referenced_message'], true);
        }

        return null;
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon|null The time that the message was sent.
     *
     * @throws \Exception
     */
    protected function getTimestampAttribute(): ?Carbon
    {
        if (! isset($this->attributes['timestamp'])) {
            return null;
        }

        return new Carbon($this->attributes['timestamp']);
    }

    /**
     * Returns the edited_timestamp attribute.
     *
     * @return Carbon|null The time that the message was edited.
     *
     * @throws \Exception
     */
    protected function getEditedTimestampAttribute(): ?Carbon
    {
        if (! isset($this->attributes['edited_timestamp'])) {
            return null;
        }

        return new Carbon($this->attributes['edited_timestamp']);
    }

    /**
     * Returns the components attribute.
     *
     * @return Collection|Component[]|null
     */
    protected function getComponentsAttribute(): ?Collection
    {
        if (! isset($this->attributes['components'])) {
            return null;
        }

        $components = Collection::for(Component::class, null);

        foreach ($this->attributes['components'] as $component) {
            $components->pushItem($this->createOf(Component::class, $component));
        }

        return $components;
    }

    /**
     * Returns the sticker_items attribute.
     *
     * @return Collection|Sticker[]|null Partial stickers.
     */
    protected function getStickerItemsAttribute(): ?Collection
    {
        if (! isset($this->attributes['sticker_items']) && ! in_array($this->type, [self::TYPE_DEFAULT, self::TYPE_REPLY])) {
            return null;
        }

        $sticker_items = Collection::for(Sticker::class);

        foreach ($this->attributes['sticker_items'] ?? [] as $sticker) {
            $sticker_items->pushItem($this->factory->part(Sticker::class, (array) $sticker, true));
        }

        return $sticker_items;
    }

    /**
     * Returns the message link attribute.
     *
     * @return string|null
     */
    public function getLinkAttribute(): ?string
    {
        if ($this->id && $this->channel_id) {
            return 'https://discord.com/channels/'.($this->guild_id ?? '@me').'/'.$this->channel_id.'/'.$this->id;
        }

        return null;
    }

    /**
     * Starts a public thread from the message.
     *
     * @link https://discord.com/developers/docs/resources/channel#start-thread-from-message
     *
     * @param array       $options                          Thread params.
     * @param string      $options['name']                  The name of the thread.
     * @param int|null    $options['auto_archive_duration'] The thread will stop showing in the channel list after `auto_archive_duration` minutes of inactivity, can be set to: of 60, 1440, 4320, 10080.
     * @param ?int|null   $options['rate_limit_per_user']   Amount of seconds a user has to wait before sending another message (0-21600).
     * @param string|null $reason                           Reason for Audit Log.
     *
     * @throws \RuntimeException      Channel type is not guild text or news.
     * @throws NoPermissionsException Missing create_public_threads permission to create or manage_threads permission to set rate_limit_per_user.
     *
     * @return ExtendedPromiseInterface<Thread>
     *
     * @since 10.0.0 Arguments for `$name` and `$auto_archive_duration` are now inside `$options`
     */
    public function startThread(array|string $options, string|null|int $reason = null, ?string $_reason = null): ExtendedPromiseInterface
    {
        // Old v7 signature
        if (is_string($options)) {
            $options = [
                'name' => $options,
            ];
            if (! is_string($reason)) {
                $options['auto_archive_duration'] = $reason ?? 1440;
            }
            $reason = $_reason;
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'name',
                'auto_archive_duration',
                'rate_limit_per_user',
            ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('auto_archive_duration', 'int')
            ->setAllowedTypes('rate_limit_per_user', ['null', 'int'])
            ->setAllowedValues('auto_archive_duration', fn ($value) => in_array($value, [60, 1440, 4320, 10080]))
            ->setAllowedValues('rate_limit_per_user', fn ($value) => $value >= 0 && $value <= 21600)
            ->setRequired('name');

        $options = $resolver->resolve($options);

        $channel = $this->channel;
        if ($channel) {
            if (! in_array($channel->type, [Channel::TYPE_GUILD_TEXT, Channel::TYPE_GUILD_ANNOUNCEMENT, null])) {
                return reject(new \RuntimeException('You can only start threads on guild text channels or news channels.'));
            }

            $botperms = $channel->getBotPermissions();
            if ($botperms && ! $botperms->create_public_threads) {
                return reject(new NoPermissionsException("You do not have permission to create public threads in channel {$this->id}."));
            }

            if (! empty($options['rate_limit_per_user']) && ! $botperms->manage_threads) {
                return reject(new NoPermissionsException("You do not have permission to manage threads in channel {$this->id}."));
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGE_THREADS, $this->channel_id, $this->id), $options, $headers)->then(function ($response) use ($channel) {
            $this->attributes['thread'] ??= $response;
            if ($channel) {
                /** @var ?Thread */
                if ($threadPart = $channel->threads->offsetGet($response->id)) {
                    $threadPart->fill((array) $response);
                } else {
                    /** @var Thread */
                    $threadPart = $channel->threads->create($response, $channel->created);
                    $threadPart->created = &$channel->created;
                }
                $channel->threads->pushItem($threadPart);

                return $threadPart;
            }

            return $this->factory->part(Thread::class, (array) $response, true);
        });
    }

    /**
     * Replies to the message.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param string|MessageBuilder $message The reply message.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function reply($message): ExtendedPromiseInterface
    {
        $channel = $this->channel;

        if ($message instanceof MessageBuilder) {
            return $channel->sendMessage($message->setReplyTo($this));
        }

        return $channel->sendMessage(MessageBuilder::new()
            ->setContent($message)
            ->setReplyTo($this));
    }

    /**
     * Crossposts the message to any following channels (publish announcement).
     *
     * @link https://discord.com/developers/docs/resources/channel#crosspost-message
     *
     * @throws \RuntimeException      Message has already been crossposted.
     * @throws NoPermissionsException Missing permission:
     *                                send_messages if this message author is the bot.
     *                                manage_messages if this message author is other user.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function crosspost(): ExtendedPromiseInterface
    {
        if ($this->crossposted) {
            return reject(new \RuntimeException('This message has already been crossposted.'));
        }

        if ($channel = $this->channel) {
            if ($botperms = $channel->getBotPermissions()) {
                if ($this->user_id == $this->discord->id) {
                    if (! $botperms->send_messages) {
                        return reject(new NoPermissionsException("You do not have permission to crosspost message in channel {$this->id}."));
                    }
                } else {
                    if (! $botperms->manage_messages) {
                        return reject(new NoPermissionsException("You do not have permission to crosspost others message in channel {$this->id}."));
                    }
                }
            }
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_CROSSPOST_MESSAGE, $this->channel_id, $this->id))->then(function ($response) {
            $this->flags = $response->flags;

            return $this;
        });
    }

    /**
     * Replies to the message after a delay.
     *
     * @see Message::reply()
     *
     * @param string|MessageBuilder $message Reply message to send after delay.
     * @param int                   $delay   Delay after text will be sent in milliseconds.
     * @param TimerInterface        &$timer  Delay timer passed by reference.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function delayedReply($message, int $delay, &$timer = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $timer = $this->discord->getLoop()->addTimer($delay / 1000, function () use ($message, $deferred) {
            $this->reply($message)->done([$deferred, 'resolve'], [$deferred, 'reject']);
        });

        return $deferred->promise();
    }

    /**
     * Deletes the message after a delay.
     *
     * @see Message::deleteMessage()
     *
     * @param int            $delay  Time to delay the delete by, in milliseconds.
     * @param TimerInterface &$timer Delay timer passed by reference.
     *
     * @return ExtendedPromiseInterface
     */
    public function delayedDelete(int $delay, &$timer = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $timer = $this->discord->getLoop()->addTimer($delay / 1000, function () use ($deferred) {
            $this->delete()->done([$deferred, 'resolve'], [$deferred, 'reject']);
        });

        return $deferred->promise();
    }

    /**
     * Reacts to the message.
     *
     * @link https://discord.com/developers/docs/resources/channel#create-reaction
     *
     * @param Emoji|string $emoticon The emoticon to react with. (custom: ':michael:251127796439449631')
     *
     * @throws NoPermissionsException Missing read_message_history permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function react($emoticon): ExtendedPromiseInterface
    {
        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        }

        if ($channel = $this->channel) {
            $botperms = $channel->getBotPermissions();
            if ($botperms && ! $botperms->read_message_history) {
                return reject(new NoPermissionsException("You do not have permission to read message history in channel {$channel->id}."));
            }
        }

        return $this->http->put(Endpoint::bind(Endpoint::OWN_MESSAGE_REACTION, $this->channel_id, $this->id, urlencode($emoticon)));
    }

    /**
     * Deletes a reaction.
     *
     * @link https://discord.com/developers/docs/resources/channel#delete-own-reaction
     * @link https://discord.com/developers/docs/resources/channel#delete-user-reaction
     *
     * @param int               $type     The type of deletion to perform.
     * @param Emoji|string|null $emoticon The emoticon to delete (if not all).
     * @param string|null       $id       The user reaction to delete (if not all).
     *
     * @throws \UnexpectedValueException Invalid reaction `$type`.
     * @throws NoPermissionsException    Missing manage_messages permission when deleting others reaction.
     *
     * @return ExtendedPromiseInterface
     */
    public function deleteReaction(int $type, $emoticon = null, ?string $id = null): ExtendedPromiseInterface
    {
        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        } else {
            $emoticon = urlencode($emoticon);
        }

        switch ($type) {
            case self::REACT_DELETE_ALL:
                $url = Endpoint::bind(Endpoint::MESSAGE_REACTION_ALL, $this->channel_id, $this->id);
                break;
            case self::REACT_DELETE_ME:
                $url = Endpoint::bind(Endpoint::OWN_MESSAGE_REACTION, $this->channel_id, $this->id, $emoticon);
                break;
            case self::REACT_DELETE_ID:
                $url = Endpoint::bind(Endpoint::USER_MESSAGE_REACTION, $this->channel_id, $this->id, $emoticon, $id);
                break;
            case self::REACT_DELETE_EMOJI:
                $url = Endpoint::bind(Endpoint::MESSAGE_REACTION_EMOJI, $this->channel_id, $this->id, $emoticon);
                break;
            default:
                return reject(new \UnexpectedValueException('Invalid reaction type'));
        }

        if (($type != self::REACT_DELETE_ME || $id != $this->discord->id) && $channel = $this->channel) {
            $botperms = $channel->getBotPermissions();
            if ($botperms && ! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete reaction by others in channel {$channel->id}."));
            }
        }

        return $this->http->delete($url);
    }

    /**
     * Edits the message.
     *
     * @link https://discord.com/developers/docs/resources/channel#edit-message
     *
     * @param MessageBuilder $message Contains the new contents of the message. Note that fields not specified in the builder will not be overwritten.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function edit(MessageBuilder $message): ExtendedPromiseInterface
    {
        return $this->_edit($message)->then(function ($response) {
            $this->fill((array) $response);

            return $this;
        });
    }

    private function _edit(MessageBuilder $message): ExtendedPromiseInterface
    {
        if ($message->requiresMultipart()) {
            $multipart = $message->toMultipart();

            return $this->http->patch(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->id), (string) $multipart, $multipart->getHeaders());
        }

        return $this->http->patch(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->id), $message);
    }

    /**
     * Deletes the message from the channel.
     *
     * @link https://discord.com/developers/docs/resources/channel#delete-message
     *
     * @return ExtendedPromiseInterface
     *
     * @throws \RuntimeException      This type of message cannot be deleted.
     * @throws NoPermissionsException Missing manage_messages permission when deleting others message.
     */
    public function delete(): ExtendedPromiseInterface
    {
        if (! $this->isDeletable()) {
            return reject(new \RuntimeException("Cannot delete this type of message: {$this->type}", 50021));
        }

        if ($this->user_id != $this->discord->id && $channel = $this->channel) {
            $botperms = $channel->getBotPermissions();
            if ($botperms && ! $botperms->manage_messages) {
                return reject(new NoPermissionsException("You do not have permission to delete message by others in channel {$channel->id}."));
            }
        }

        return $this->http->delete(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->id));
    }

    /**
     * Creates a reaction collector for the message.
     *
     * @param callable $filter           The filter function. Returns true or false.
     * @param int      $options['time']  Time in milliseconds until the collector finishes or false.
     * @param int      $options['limit'] The amount of reactions allowed or false.
     *
     * @return ExtendedPromiseInterface<Collection<MessageReaction>>
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
                $reactions->pushItem($reaction);

                if ($options['limit'] !== false && count($reactions) >= $options['limit']) {
                    $this->discord->removeListener(Event::MESSAGE_REACTION_ADD, $eventHandler);
                    $deferred->resolve($reactions);

                    if (null !== $timer) {
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
     * @return ExtendedPromiseInterface<Message>
     */
    public function addEmbed(Embed $embed): ExtendedPromiseInterface
    {
        return $this->edit(MessageBuilder::new()
            ->addEmbed($embed));
    }

    /**
     * Whether this type of message can be deleted (not due to permission).
     *
     * @return bool true if this message can be deleted.
     */
    public function isDeletable(): bool
    {
        return ! in_array($this->type, [
            self::TYPE_RECIPIENT_ADD,
            self::TYPE_RECIPIENT_REMOVE,
            self::TYPE_CALL,
            self::TYPE_CHANNEL_NAME_CHANGE,
            self::TYPE_CHANNEL_ICON_CHANGE,
            self::TYPE_GUILD_DISCOVERY_DISQUALIFIED,
            self::TYPE_GUILD_DISCOVERY_REQUALIFIED,
            self::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING,
            self::TYPE_GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING,
            self::TYPE_THREAD_STARTER_MESSAGE,
            self::TYPE_GUILD_APPLICATION_PREMIUM_SUBSCRIPTION,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/channel#edit-message-jsonform-params
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'content' => $this->content,
            'flags' => $this->flags,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'channel_id' => $this->channel_id,
            'message_id' => $this->id,
        ];
    }
}
