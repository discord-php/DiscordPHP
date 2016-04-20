<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Cache\Cache;
use Discord\Parts\Part;
use Discord\Parts\User\Member;

/**
 * VoiceStateUpdate Class.
 *
 * @property string $channel_id
 * @property bool   $deaf
 * @property string $guild_id
 * @property bool   $mute
 * @property bool   $self_deaf
 * @property bool   $self_mute
 * @property string $session_id
 * @property string $supress
 * @property string $token
 * @property string $user_id
 */
class VoiceStateUpdate extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'channel_id',
        'deaf',
        'guild_id',
        'mute',
        'self_deaf',
        'self_mute',
        'session_id',
        'supress',
        'token',
        'user_id',
    ];

    /**
     * Gets the member attribute.
     *
     * @return Member|null The member attribute.
     */
    public function getMemberAttribute()
    {
        return Cache::get("guild.{$this->guild_id}.members.{$this->user_id}");
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null The channel attribute.
     */
    public function getChannelAttribute()
    {
        return Cache::get("channel.{$this->channel_id}");
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    public function getGuildAttribute()
    {
        return Cache::aget("guild.{$this->guild_id}");
    }
}
