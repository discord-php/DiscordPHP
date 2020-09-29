<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Notifies the client of voice state updates about users.
 *
 * @property string $guild_id
 * @property string $channel_id
 * @property string $user_id
 * @property Member $member
 * @property Guild $guild
 * @property Channel $channel
 * @property User $user
 * @property string $session_id
 * @property bool $deaf
 * @property bool $mute
 * @property bool $self_deaf
 * @property bool $self_mute
 * @property bool $self_stream
 * @property bool $self_video
 * @property bool $suppress
 */
class VoiceStateUpdate extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'user_id',
        'member',
        'session_id',
        'deaf',
        'mute',
        'self_deaf',
        'self_mute',
        'self_stream',
        'self_video',
        'suppress',
    ];

    /**
     * Gets the member attribute.
     *
     * @return ?Member    The member attribute.
     * @throws \Exception
     */
    protected function getMemberAttribute(): ?Part
    {
        if ($this->guild) {
            if ($member = $this->guild->members->get('id', $this->user_id)) {
                return $member;
            }
        }

        return $this->factory->create(Member::class, $this->attributes, true);
    }

    /**
     * Gets the channel attribute.
     *
     * @return ?Channel The channel attribute.
     */
    protected function getChannelAttribute(): ?Part
    {
        if ($this->guild) {
            return $this->guild->channels->get('id', $this->channel_id);
        }
    }

    /**
     * Gets the user attribute.
     *
     * @return ?User      The user attribute.
     * @throws \Exception
     */
    protected function getUserAttribute(): ?Part
    {
        if ($user = $this->discord->users->get('id', $this->user_id)) {
            return $user;
        }
        if ($this->attributes['member']->user !== null) {
            return $this->factory->create(User::class, $this->attributes['member']->user, true);
        }
    }

    /**
     * Gets the guild attribute.
     *
     * @return ?Guild The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}
