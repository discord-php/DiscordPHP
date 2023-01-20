<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Represents a specific reaction to a message by a specific user.
 * Different from `Reaction` in the fact that `Reaction` represents a specific
 * reaction to a message by _multiple_ members.
 *
 * @since 5.0.0
 *
 * @property      string|null    $user_id    ID of the user that performed the reaction.
 * @property      User|null      $user       User that performed the reaction.
 * @property      string         $channel_id ID of the channel that the reaction was performed in.
 * @property-read Channel|Thread $channel    Channel that the reaction was performed in.
 * @property      string         $message_id ID of the message that the reaction was placed on.
 * @property      Message|null   $message    Message that the reaction was placed on, null if not cached.
 * @property      string|null    $guild_id   ID of the guild that owns the channel.
 * @property-read Guild|null     $guild      Guild that owns the channel.
 * @property      Member|null    $member     Member object of the user that performed the reaction, null if not cached or DM channel.
 * @property      Emoji|null     $emoji      The emoji that was used as the reaction.
 *
 * @property string $reaction_id ID of the reaction.
 */
class MessageReaction extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'user_id',
        'channel_id',
        'message_id',
        'guild_id',
        'member',
        'emoji',
    ];

    /**
     * {@inheritDoc}
     */
    public function isPartial(): bool
    {
        return $this->user === null ||
            $this->message === null ||
            $this->member === null;
    }

    /**
     * {@inheritDoc}
     */
    public function fetch(): ExtendedPromiseInterface
    {
        $promise = resolve();

        if ($this->member === null) {
            $promise = $promise
                ->then(function () {
                    return $this->http->get(Endpoint::bind(Endpoint::GUILD_MEMBER, $this->guild_id, $this->user_id));
                })
                ->then(function ($member) {
                    $this->attributes['member'] = $this->factory->part(Member::class, (array) $member, true);
                });
        }

        if ($this->message === null) {
            $promise = $promise
                ->then(function () {
                    return $this->http->get(Endpoint::bind(Endpoint::CHANNEL_MESSAGE, $this->channel_id, $this->message_id));
                })
                ->then(function ($message) {
                    $this->attributes['message'] = $this->factory->part(Message::class, (array) $message, true);
                });
        }

        return $promise->then(function () {
            return $this;
        });
    }

    /**
     * Gets the ID of the reaction.
     *
     * @return string
     *
     * @since 10.0.0 Changed to only return custom emoji id or unicode emoji name.
     */
    protected function getReactionIdAttribute(): string
    {
        return $this->emoji->id ?? $this->emoji->name;
    }

    /**
     * Gets the user attribute.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        if ($member = $this->member) {
            return $member->user;
        }

        if ($user = $this->discord->users->get('id', $this->user_id)) {
            return $user;
        }

        return $this->attributes['user'] ?? null;
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

            return null;
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
     * Gets the message attribute.
     *
     * @return Message|null
     */
    protected function getMessageAttribute(): ?Message
    {
        if ($channel = $this->channel) {
            if ($message = $channel->messages->get('id', $this->message_id)) {
                return $message;
            }
        }

        return $this->attributes['message'] ?? null;
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($this->guild_id) {
            return $this->discord->guilds->get('id', $this->guild_id);
        }

        return null;
    }

    /**
     * Gets the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($this->user_id && $guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->user_id)) {
                return $member;
            }
        }

        if (isset($this->attributes['member'])) {
            return $this->factory->part(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }

    /**
     * Gets the emoji attribute.
     *
     * @return Emoji
     */
    protected function getEmojiAttribute(): Emoji
    {
        return $this->factory->part(Emoji::class, (array) $this->attributes['emoji'], true);
    }

    /**
     * Delete this reaction.
     *
     * @param int|null $type The type of deletion to perform.
     *
     * @throws \RuntimeException Reaction has no user id.
     *
     * @return ExtendedPromiseInterface
     *
     * @see Message::deleteReaction()
     *
     * @link https://discord.com/developers/docs/resources/channel#delete-own-reaction
     * @link https://discord.com/developers/docs/resources/channel#delete-user-reaction
     */
    public function delete(?int $type = null): ExtendedPromiseInterface
    {
        if ($type === null) {
            if ($this->user_id == $this->discord->id) {
                $type = Message::REACT_DELETE_ME;
            } else {
                $type = Message::REACT_DELETE_ID;
            }
        }

        $emoji = urlencode($this->emoji->id === null ? $this->emoji->name : "{$this->emoji->name}:{$this->emoji->id}");

        switch ($type) {
            case Message::REACT_DELETE_ALL:
                $url = Endpoint::bind(Endpoint::MESSAGE_REACTION_ALL, $this->channel_id, $this->message_id);
                break;
            case Message::REACT_DELETE_ME:
                $url = Endpoint::bind(Endpoint::OWN_MESSAGE_REACTION, $this->channel_id, $this->message_id, $emoji);
                break;
            case Message::REACT_DELETE_EMOJI:
                $url = Endpoint::bind(Endpoint::MESSAGE_REACTION_EMOJI, $this->channel_id, $this->message_id, $emoji);
                break;
            case Message::REACT_DELETE_ID:
            default:
                if (! $userid = $this->user_id ?? $this->user->id) {
                    return reject(new \RuntimeException('This reaction has no user id'));
                }
                $url = Endpoint::bind(Endpoint::USER_MESSAGE_REACTION, $this->channel_id, $this->message_id, $emoji, $userid);
                break;
        }

        return $this->http->delete($url);
    }
}
