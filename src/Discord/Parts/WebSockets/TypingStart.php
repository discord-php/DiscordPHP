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

use Carbon\Carbon;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A TypingStart part is used when the `TYPING_START` event is fired on the WebSocket. It contains
 * information such as when the event was fired and then channel it was fired in.
 *
 * @link https://discord.com/developers/docs/topics/gateway#typing-start
 *
 * @property      string              $channel_id The unique identifier of the channel that the user started typing in.
 * @property-read Channel|Thread|null $channel    The channel that the user started typing in.
 * @property      string|null         $guild_id   The unique identifier of the guild that the user started typing in.
 * @property-read Guild|null          $guild      The guild that the user started typing in.
 * @property      string              $user_id    The unique identifier of the user that started typing
 * @property-read User|null           $user       The user that started typing.
 * @property      Carbon              $timestamp  A timestamp of when the user started typing.
 * @property      Member|null         $member     The member that started typing.
 */
class TypingStart extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'channel_id',
        'guild_id',
        'user_id',
        'timestamp',
        'member',
    ];

    /**
     * Gets the channel attribute.
     *
     * @return Channel|Thread|null The channel that the user started typing in.
     */
    protected function getChannelAttribute()
    {
        if ($guild = $this->guild) {
            if ($channel = $guild->channels->get('id', $this->channel_id)) {
                return $channel;
            }

            foreach ($guild->channels as $channel) {
                if ($thread = $channel->threads->get('id', $this->channel_id)) {
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
     * Gets the guild attribute.
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
     * Gets the user attribute.
     *
     * @return User|null The user that started typing.
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Gets the timestamp attribute.
     *
     * @throws \Exception
     *
     * @return Carbon The time that the user started typing.
     */
    protected function getTimestampAttribute(): Carbon
    {
        return new Carbon($this->attributes['timestamp']);
    }

    /**
     * Gets the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            if ($member = $guild->members->get('id', $this->user_id)) {
                return $member;
            }
        }

        if (isset($this->attributes['member'])) {
            return $this->factory->part(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
        }

        return null;
    }
}
