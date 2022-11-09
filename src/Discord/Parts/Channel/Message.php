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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\Interactions\Request\Component;
use Discord\Parts\Thread\Thread;
use Discord\Parts\WebSockets\MessageInteraction;
use Discord\Repository\Channel\ReactionRepository;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;

/**
 * A message which is posted to a Discord text channel.
 *
 * @see https://discord.com/developers/docs/resources/channel#message-object
 *
 * @property string                      $id                                     The unique identifier of the message.
 * @property string                      $channel_id                             The unique identifier of the channel that the message was went in.
 * @property Channel|Thread|null         $channel                                The channel that the message was sent in.
 * @property string|null                 $guild_id                               The unique identifier of the guild that the channel the message was sent in belongs to.
 * @property Guild|null                  $guild                                  The guild that the message was sent in.
 * @property User|null                   $author                                 The author of the message. Will be a webhook if sent from one.
 * @property string|null                 $user_id                                The user id of the author.
 * @property Member|null                 $member                                 The member that sent this message, or null if it was in a private message.
 * @property string                      $content                                The content of the message if it is a normal message.
 * @property Carbon                      $timestamp                              A timestamp of when the message was sent.
 * @property Carbon|null                 $edited_timestamp                       A timestamp of when the message was edited, or null.
 * @property bool                        $tts                                    Whether the message was sent as a text-to-speech message.
 * @property bool                        $mention_everyone                       Whether the message contained an @everyone mention.
 * @property Collection|User[]           $mentions                               A collection of the users mentioned in the message.
 * @property Collection|Role[]           $mention_roles                          A collection of roles that were mentioned in the message.
 * @property Collection|Channel[]        $mention_channels                       Collection of mentioned channels.
 * @property Collection|Attachment[]     $attachments                            Collection of attachment objects.
 * @property Collection|Embed[]          $embeds                                 A collection of embed objects.
 * @property ReactionRepository          $reactions                              Collection of reactions on the message.
 * @property string|null                 $nonce                                  A randomly generated string that provides verification for the client. Not required.
 * @property bool                        $pinned                                 Whether the message is pinned to the channel.
 * @property string|null                 $webhook_id                             ID of the webhook that made the message, if any.
 * @property int                         $type                                   The type of message.
 * @property object|null                 $activity                               Current message activity. Requires rich presence.
 * @property object|null                 $application                            Application of message. Requires rich presence.
 * @property string|null                 $application_id                         If the message is a response to an Interaction, this is the id of the interaction's application.
 * @property object|null                 $message_reference                      Message that is referenced by this message.
 * @property int|null                    $flags                                  Message flags.
 * @property Message|null                $referenced_message                     The message that is referenced in a reply.
 * @property MessageInteraction|null     $interaction                            Sent if the message is a response to an Interaction.
 * @property Thread|null                 $thread                                 The thread that the message was sent in.
 * @property Collection|Component[]|null $components                             Sent if the message contains components like buttons, action rows, or other interactive components.
 * @property Collection|Sticker[]|null   $sticker_items                          Stickers attached to the message.
 * @property int|null                    $position                               A generally increasing integer (there may be gaps or duplicates) that represents the approximate position of the message in a thread, it can be used to estimate the relative position of the message in a thread in company with `total_message_sent` on parent thread.
 * @property bool                        $crossposted                            Message has been crossposted.
 * @property bool                        $is_crosspost                           Message is a crosspost from another channel.
 * @property bool                        $suppress_embeds                        Do not include embeds when serializing message.
 * @property bool                        $source_message_deleted                 Source message for this message has been deleted.
 * @property bool                        $urgent                                 Message is urgent.
 * @property bool                        $has_thread                             Whether this message has an associated thread, with the same id as the message.
 * @property bool                        $ephemeral                              Whether this message is only visible to the user who invoked the Interaction.
 * @property bool                        $loading                                Whether this message is an Interaction Response and the bot is "thinking".
 * @property bool                        $failed_to_mention_some_roles_in_thread This message failed to mention some roles and add their members to the thread.
 * @property string|null                 $link                                   Returns a link to the message.
 */
class Message extends Part
{
    // @todo next major version TYPE_ name consistency
    public const TYPE_NORMAL = 0;
    public const TYPE_USER_ADDED = 1;
    public const TYPE_USER_REMOVED = 2;
    public const TYPE_CALL = 3;
    public const TYPE_CHANNEL_NAME_CHANGE = 4;
    public const TYPE_CHANNEL_ICON_CHANGE = 5;
    public const CHANNEL_PINNED_MESSAGE = 6;
    public const TYPE_USER_JOIN = 7;
    public const TYPE_GUILD_BOOST = 8;
    public const TYPE_GUILD_BOOST_TIER_1 = 9;
    public const TYPE_GUILD_BOOST_TIER_2 = 10;
    public const TYPE_GUILD_BOOST_TIER_3 = 11;
    public const CHANNEL_FOLLOW_ADD = 12;
    public const GUILD_DISCOVERY_DISQUALIFIED = 14;
    public const GUILD_DISCOVERY_REQUALIFIED = 15;
    public const GUILD_DISCOVERY_GRACE_PERIOD_INITIAL_WARNING = 16;
    public const GUILD_DISCOVERY_GRACE_PERIOD_FINAL_WARNING = 17;
    public const TYPE_THREAD_CREATED = 18;
    public const TYPE_REPLY = 19;
    public const TYPE_APPLICATION_COMMAND = 20;
    public const TYPE_THREAD_STARTER_MESSAGE = 21;
    public const TYPE_GUILD_INVITE_REMINDER = 22;
    public const TYPE_CONTEXT_MENU_COMMAND = 23;
    public const TYPE_AUTO_MODERATION_ACTION = 24;

    /** @deprecated 7.1.0 Use `Message::TYPE_USER_JOIN` */
    public const GUILD_MEMBER_JOIN = 7;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION = 8;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_1` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_1 = 9;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_2` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_2 = 10;
    /** @deprecated 7.1.0 Use `Message::TYPE_GUILD_BOOST_TIER_3` */
    public const USER_PREMIUM_GUILD_SUBSCRIPTION_TIER_3 = 11;

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

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'reactions',
        'attachments',
        'tts',
        'embeds',
        'timestamp',
        'mention_everyone',
        'id',
        'pinned',
        'edited_timestamp',
        'author',
        'mention_roles',
        'mention_channels',
        'content',
        'channel_id',
        'mentions',
        'type',
        'flags',
        'message_reference',
        'nonce',
        'member',
        'guild_id',
        'webhook_id',
        'activity',
        'application',
        'application_id',
        'referenced_message',
        'interaction',
        'components',
        'sticker_items',
        'stickers',
        'position',
    ];

    /**
     * @inheritdoc
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
     * Gets the mention_channels attribute.
     *
     * @return Collection|Channel[]
     */
    protected function getMentionChannelsAttribute(): Collection
    {
        $collection = new Collection([], 'id', Channel::class);

        if (preg_match_all('/<#([0-9]*)>/', $this->content, $matches)) {
            foreach ($matches[1] as $channelId) {
                if ($channel = $this->discord->getChannel($channelId)) {
                    $collection->pushItem($channel);
                }
            }
        }

        foreach ($this->attributes['mention_channels'] ?? [] as $mention_channel) {
            if (! $channel = $this->discord->getChannel($mention_channel->id)) {
                $channel = $this->factory->create(Channel::class, $mention_channel, true);
            }

            $collection->pushItem($channel);
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
            $attachments->pushItem($this->factory->part(Attachment::class, (array) $attachment, true));
        }

        return $attachments;
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
            $this->reactions->pushItem($this->reactions->create((array) $reaction, true));
        }
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|Thread The channel or thread the message was sent in.
     */
    protected function getChannelAttribute(): Part
    {
        if ($this->guild && $channel = $this->guild->channels->offsetGet($this->channel_id)) {
            return $channel;
        }

        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        if ($thread = $this->thread) {
            return $thread;
        }

        return $this->factory->create(Channel::class, [
            'id' => $this->channel_id,
            'type' => Channel::TYPE_DM,
        ], true);
    }

    /**
     * Returns the thread which the message was sent in.
     *
     * @return Thread|null
     */
    protected function getThreadAttribute(): ?Thread
    {
        if ($this->guild) {
            foreach ($this->guild->channels as $channel) {
                if ($thread = $channel->threads->get('id', $this->channel_id)) {
                    return $thread;
                }
            }
        }

        return null;
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
     * @return Collection The roles that were mentioned.
     */
    protected function getMentionRolesAttribute(): Collection
    {
        $roles = new Collection();

        if ($this->channel->guild) {
            foreach ($this->channel->guild->roles ?? [] as $role) {
                if (in_array($role->id, $this->attributes['mention_roles'] ?? [])) {
                    $roles->pushItem($role);
                }
            }
        }

        return $roles;
    }

    /**
     * Returns the mention attribute.
     *
     * @return Collection The users that were mentioned.
     */
    protected function getMentionsAttribute(): Collection
    {
        $users = new Collection();

        foreach ($this->attributes['mentions'] ?? [] as $mention) {
            if (! $user = $this->discord->users->get('id', $mention->id)) {
                $user = $this->factory->create(User::class, $mention, true);
            }
            $users->pushItem($user);
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
        if (isset($this->attributes['author'])) {
            if ($user = $this->discord->users->get('id', $this->attributes['author']->id)) {
                return $user;
            }

            return $this->factory->create(User::class, $this->attributes['author'], true);
        }

        return null;
    }

    /**
     * Returns the member attribute.
     *
     * @return Member|null The member that sent the message, or null if it was in a private message.
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($this->channel->guild && $author = $this->channel->guild->members->get('id', $this->attributes['author']->id)) {
            return $author;
        }

        if (isset($this->attributes['member'])) {
            return $this->factory->create(Member::class, array_merge((array) $this->attributes['member'], [
                'user' => $this->attributes['author'],
                'guild_id' => $this->guild_id,
            ]), true);
        }

        return null;
    }

    /**
     * Returns the embed attribute.
     *
     * @return Collection|Embed[] A collection of embeds.
     */
    protected function getEmbedsAttribute(): Collection
    {
        $embeds = new Collection([], null);

        foreach ($this->attributes['embeds'] ?? [] as $embed) {
            $embeds->pushItem($this->factory->create(Embed::class, $embed, true));
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
        if (isset($this->attributes['interaction'])) {
            return $this->factory->part(MessageInteraction::class, (array) $this->attributes['interaction'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
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
        if (isset($this->attributes['message_reference'])) {
            $reference = $this->attributes['message_reference'];

            if ($channel = $this->discord->getChannel($reference->channel_id ?? null)) {
                if ($message = $channel->messages->get('id', $reference->message_id ?? null)) {
                    return $message;
                }
            }
        }

        if (isset($this->attributes['referenced_message'])) {
            return $this->factory->create(Message::class, $this->attributes['referenced_message'], true);
        }

        return null;
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon|null The time that the message was sent.
     */
    protected function getTimestampAttribute(): ?Carbon
    {
        if (isset($this->attributes['timestamp'])) {
            return new Carbon($this->attributes['timestamp']);
        }

        return null;
    }

    /**
     * Returns the edited_timestamp attribute.
     *
     * @return Carbon|null The time that the message was edited.
     */
    protected function getEditedTimestampAttribute(): ?Carbon
    {
        if (isset($this->attributes['edited_timestamp'])) {
            return new Carbon($this->attributes['edited_timestamp']);
        }

        return null;
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
            $components->pushItem($this->factory->create(Component::class, $component, true));
        }

        return $components;
    }

    /**
     * Returns the sticker_items attribute.
     *
     * @return Collection|Sticker[]|null
     */
    protected function getStickerItemsAttribute(): ?Collection
    {
        if (! isset($this->attributes['sticker_items'])) {
            return null;
        }

        $sticker_items = Collection::for(Sticker::class);

        foreach ($this->attributes['sticker_items'] as $sticker) {
            $sticker_items->pushItem($this->factory->create(Sticker::class, $sticker, true));
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
     * @see https://discord.com/developers/docs/resources/channel#start-thread-from-message
     *
     * @param string      $name                  The name of the thread.
     * @param int         $auto_archive_duration Number of minutes of inactivity until the thread is auto-archived. One of 60, 1440, 4320, 10080.
     * @param string|null $reason                Reason for Audit Log.
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     *
     * @return ExtendedPromiseInterface<Thread>
     */
    public function startThread(string $name, int $auto_archive_duration = 1440, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! in_array($this->channel->type, [Channel::TYPE_TEXT, Channel::TYPE_NEWS])) {
            return reject(new \RuntimeException('You can only start threads on guild text channels or news channels.'));
        }

        if (! in_array($auto_archive_duration, [60, 1440, 4320, 10080])) {
            return reject(new \UnexpectedValueException('`auto_archive_duration` must be one of 60, 1440, 4320, 10080.'));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_MESSAGE_THREADS, $this->channel_id, $this->id), [
            'name' => $name,
            'auto_archive_duration' => $auto_archive_duration,
        ], $headers)->then(function ($response) {
            return $this->factory->create(Thread::class, $response, true);
        });
    }

    /**
     * Replies to the message.
     *
     * @see https://discord.com/developers/docs/resources/channel#create-message
     *
     * @param string|MessageBuilder $message The reply message.
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function reply($message): ExtendedPromiseInterface
    {
        if ($message instanceof MessageBuilder) {
            return $this->channel->sendMessage($message->setReplyTo($this));
        }

        return $this->channel->sendMessage(MessageBuilder::new()
            ->setContent($message)
            ->setReplyTo($this));
    }

    /**
     * Crossposts the message to any following channels.
     *
     * @see https://discord.com/developers/docs/resources/channel#crosspost-message
     *
     * @return ExtendedPromiseInterface<Message>
     */
    public function crosspost(): ExtendedPromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::CHANNEL_CROSSPOST_MESSAGE, $this->channel_id, $this->id))->then(function ($response) {
            return $this->factory->create(Message::class, $response, true);
        });
    }

    /**
     * Replies to the message after a delay.
     *
     * @see Message::reply()
     *
     * @param string|MessageBuilder           $message Reply message to send after delay.
     * @param int                             $delay   Delay after text will be sent in milliseconds.
     * @param \React\EventLoop\TimerInterface &$timer  Delay timer passed by reference.
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
     * @param int                             $delay  Time to delay the delete by, in milliseconds.
     * @param \React\EventLoop\TimerInterface &$timer Delay timer passed by reference.
     *
     * @return ExtendedPromseInterface
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
     * @see https://discord.com/developers/docs/resources/channel#create-reaction
     *
     * @param Emoji|string $emoticon The emoticon to react with. (custom: ':michael:251127796439449631')
     *
     * @return ExtendedPromiseInterface
     */
    public function react($emoticon): ExtendedPromiseInterface
    {
        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        }

        return $this->http->put(Endpoint::bind(Endpoint::OWN_MESSAGE_REACTION, $this->channel_id, $this->id, urlencode($emoticon)));
    }

    /**
     * Deletes a reaction.
     *
     * @see https://discord.com/developers/docs/resources/channel#delete-own-reaction
     * @see https://discord.com/developers/docs/resources/channel#delete-user-reaction
     *
     * @param int               $type     The type of deletion to perform.
     * @param Emoji|string|null $emoticon The emoticon to delete (if not all).
     * @param string|null       $id       The user reaction to delete (if not all).
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

        return $this->http->delete($url);
    }

    /**
     * Edits the message.
     *
     * @see https://discord.com/developers/docs/resources/channel#edit-message
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
     * @see https://discord.com/developers/docs/resources/channel#delete-message
     *
     * @return ExtendedPromiseInterface
     */
    public function delete(): ExtendedPromiseInterface
    {
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
     * @return ExtendedPromiseInterface<Message>
     */
    public function addEmbed(Embed $embed): ExtendedPromiseInterface
    {
        return $this->edit(MessageBuilder::new()
            ->addEmbed($embed));
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'content' => $this->content,
            'flags' => $this->flags,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'message_id' => $this->id,
            'channel_id' => $this->channel_id,
            'guild_id' => $this->guild_id,
        ];
    }
}
