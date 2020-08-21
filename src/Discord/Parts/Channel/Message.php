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
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use React\Promise\Deferred;

/**
 * A message which is posted to a Discord text channel.
 *
 * @property string                         $id               The unique identifier of the message.
 * @property \Discord\Parts\Channel\Channel $channel          The channel that the message was sent in.
 * @property string                         $channel_id       The unique identifier of the channel that the message was went in.
 * @property string                         $content          The content of the message if it is a normal message.
 * @property int                            $type             The type of message.
 * @property Collection[User]               $mentions         A collection of the users mentioned in the message.
 * @property \Discord\Parts\User\Member     $author           The author of the message.
 * @property bool                           $mention_everyone Whether the message contained an @everyone mention.
 * @property Carbon                         $timestamp        A timestamp of when the message was sent.
 * @property Carbon|null                    $edited_timestamp A timestamp of when the message was edited, or null.
 * @property bool                           $tts              Whether the message was sent as a text-to-speech message.
 * @property array                          $attachments      An array of attachment objects.
 * @property Collection[Embed]              $embeds           A collection of embed objects.
 * @property string|null                    $nonce            A randomly generated string that provides verification for the client. Not required.
 * @property Collection[Role]               $mention_roles    A collection of roles that were mentioned in the message.
 * @property bool                           $pinned           Whether the message is pinned to the channel.
 * @property Collection[Channel]            $mention_channels Collection of mentioned channels.
 * @property Collection[Reaction]           $reactions        Collection of reactions on the message.
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
     * Gets the crossposted attribute.
     *
     * @return bool
     */
    public function getCrosspostedAttribute()
    {
        return (bool) ($this->flags & (1 << 0));
    }

    /**
     * Gets the is_crosspost attribute.
     *
     * @return bool
     */
    public function getIsCrosspostAttribute()
    {
        return (bool) ($this->flags & (1 << 1));
    }

    /**
     * Gets the suppress_embeds attribute.
     *
     * @return bool
     */
    public function getSuppressEmbedsAttribute()
    {
        return (bool) ($this->flags & (1 << 2));
    }

    /**
     * Gets the source_message_deleted attribute.
     *
     * @return bool
     */
    public function getSourceMessageDeletedAttribute()
    {
        return (bool) ($this->flags & (1 << 3));
    }

    /**
     * Gets the urgent attribute.
     *
     * @return bool
     */
    public function getUrgentAttribute()
    {
        return (bool) ($this->flags & (1 << 4));
    }

    /**
     * Gets the mention_channels attribute.
     *
     * @return Collection[Channel]
     */
    public function getMentionChannelsAttribute()
    {
        $collection = new Collection();

        if (isset($this->attributes['mention_channels'])) {
            foreach ($this->attributes['mention_channels'] as $channel) {
                $collection->push($this->factory->create(Channel::class, $channel, true));
            }
        }

        return $collection;
    }

    /**
     * Gets the reactions attribute.
     *
     * @return Collection[Reaction]
     */
    public function getReactionsAttribute()
    {
        $collection = new Collection();

        if (isset($this->attributes['reactions'])) {
            foreach ($this->attributes['reactions'] as $reaction) {
                $collection->push($this->factory->create(Reaction::class, $reaction, true));
            }
        }

        return $collection;
    }

    /**
     * Replies to the message.
     *
     * @param string $text The text to reply with.
     *
     * @return \React\Promise\Promise
     */
    public function reply($text)
    {
        return $this->channel->sendMessage("{$this->author}, {$text}");
    }

    /**
     * Reacts to the message.
     *
     * @param Emoji|string $emoticon The emoticon to react with. (custom: ':michael:251127796439449631')
     *
     * @return \React\Promise\Promise
     */
    public function react($emoticon)
    {
        $deferred = new Deferred();

        if ($emoticon instanceof Emoji) {
            $emoticon = $emoticon->toReactionString();
        }

        $this->http->put(
            "channels/{$this->channel->id}/messages/{$this->id}/reactions/{$emoticon}/@me"
        )->then(
            \React\Partial\bind_right($this->resolve, $deferred),
            \React\Partial\bind_right($this->reject, $deferred)
        );

        return $deferred->promise();
    }

    /**
     * Deletes a reaction.
     *
     * @param int               $type     The type of deletion to perform.
     * @param Emoji|string|null $emoticon The emoticon to delete (if not all).
     * @param string            $id       The user reaction to delete (if not all).
     *
     * @return \React\Promise\Promise
     */
    public function deleteReaction($type, $emoticon = null, $id = null)
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
            )->then(
                \React\Partial\bind_right($this->resolve, $deferred),
                \React\Partial\bind_right($this->reject, $deferred)
            );
        } else {
            $deferred->reject();
        }

        return $deferred->promise();
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel The channel the message was sent in.
     */
    public function getChannelAttribute()
    {
        foreach ($this->discord->guilds as $guild) {
            if ($channel = $guild->channels->get('id', $this->channel_id)) {
                return $channel;
            }
        }

        if ($channel = $this->discord->private_channels->get('id', $this->channel_id)) {
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
    public function getMentionRolesAttribute()
    {
        $roles = new Collection([], 'id');

        foreach ($this->channel->guild->roles as $role) {
            if (array_search($role->id, $this->attributes['mention_roles']) !== false) {
                $roles->push($role);
            }
        }

        return $roles;
    }

    /**
     * Returns the mention attribute.
     *
     * @return Collection The users that were mentioned.
     */
    public function getMentionsAttribute()
    {
        $users = new Collection([], 'id');

        foreach ($this->attributes['mentions'] as $mention) {
            $users->push($this->factory->create(User::class, $mention, true));
        }

        return $users;
    }

    /**
     * Returns the author attribute.
     *
     * @return Member|User The member that sent the message. Will return a User object if it is a PM.
     */
    public function getAuthorAttribute()
    {
        if ($this->channel->type != Channel::TYPE_TEXT) {
            return $this->factory->create(User::class, $this->attributes['author'], true);
        }

        return $this->channel->guild->members->get('id', $this->attributes['author']->id);
    }

    /**
     * Returns the embed attribute.
     *
     * @return Collection A collection of embeds.
     */
    public function getEmbedsAttribute()
    {
        $embeds = new Collection();

        foreach ($this->attributes['embeds'] as $embed) {
            $embeds->push($this->factory->create(Embed::class, $embed, true));
        }

        return $embeds;
    }

    /**
     * Returns the timestamp attribute.
     *
     * @return Carbon The time that the message was sent.
     */
    public function getTimestampAttribute()
    {
        return new Carbon($this->attributes['timestamp']);
    }

    /**
     * Returns the edited_timestamp attribute.
     *
     * @return Carbon|null The time that the message was edited.
     */
    public function getEditedTimestampAttribute()
    {
        if (! $this->attributes['edited_timestamp']) {
            return;
        }

        return new Carbon($this->attributes['edited_timestamp']);
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [
            'content' => $this->content,
            'mentions' => $this->mentions,
            'tts' => $this->tts,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'content' => $this->content,
            'mentions' => $this->mentions,
        ];
    }
}
