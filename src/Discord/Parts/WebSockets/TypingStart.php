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
 * @property User    $user       The user that started typing.
 * @property Member  $member     The member that started typing.
 * @property string  $user_id    The unique identifier of the user that started typing
 * @property Carbon  $timestamp  A timestamp of when the user started typing.
 * @property Channel $channel    The channel that the user started typing in.
 * @property string  $channel_id The unique identifier of the channel that the user started typing in.
 * @property Guild   $guild      The guild that the user started typing in.
 * @property string  $guild_id   The unique identifier of the guild that the user started typing in.
 */
class TypingStart extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['user_id', 'timestamp', 'channel_id', 'guild_id', 'member'];

    /**
     * Gets the user attribute.
     *
     * @return User The user that started typing.
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Gets the timestamp attribute.
     *
     * @return Carbon     The time that the user started typing.
     * @throws \Exception
     */
    protected function getTimestampAttribute(): Carbon
    {
        return new Carbon(gmdate('r', $this->attributes['timestamp']));
    }

    /**
     * Gets the member attribute.
     *
     * @return Member
     * @throws \Exception
     */
    protected function getMemberAttribute(): Part
    {
        if ($this->guild && $member = $this->guild->members->get('id', $this->user_id)) {
            return $member;
        }

        return $this->factory->create(Member::class, $this->attributes['member'], true);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel The channel that the user started typing in.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->guild) {
            return $this->guild->channels->get('id', $this->attributes['channel_id']);
        }

        return $this->discord->private_channels->get('id', $this->attributes['channel_id']);
    }

    /**
     * Gets the guild attribute.
     *
     * @return ?Guild
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->attributes['guild_id']);
    }
}
