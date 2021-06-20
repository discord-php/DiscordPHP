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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Notifies the client of voice state updates about users.
 *
 * @property string|null  $guild_id                   Guild ID of the guild that owns the channel relating to the voice state. Null if for a DM/group channel.
 * @property string|null  $channel_id                 Channel ID of the channel that the voice state is for. Null if the user has left the channel.
 * @property string       $user_id                    User ID of the user that the voice state is for.
 * @property Member|null  $member                     Member object the voice state is for. Null if for a DM/group channel.
 * @property Guild|null   $guild                      Guild object that owns the channel relating to the voice state. Null if for a DM/group channel.
 * @property Channel|null $channel                    Channel that the voice state is for. Null if the user has left the channel.
 * @property User|null    $user                       User that the voice state is for.
 * @property string       $session_id                 Session ID for this voice state.
 * @property bool         $deaf
 * @property bool         $mute
 * @property bool         $self_deaf
 * @property bool         $self_mute
 * @property bool         $self_stream
 * @property bool         $self_video
 * @property bool         $suppress
 * @property Carbon|null  $request_to_speak_timestamp Time the user requested to speak.
 */
class VoiceStateUpdate extends Part
{
    /**
     * @inheritdoc
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
        'request_to_speak_timestamp',
    ];

    /**
     * Gets the member attribute.
     *
     * @return Member|null The member attribute.
     */
    protected function getMemberAttribute(): ?Member
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
     * @return Channel|null The channel attribute.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->channel_id) {
            return $this->guild->channels->get('id', $this->channel_id);
        }

        return null;
    }

    /**
     * Gets the user attribute.
     *
     * @return User|null The user attribute.
     */
    protected function getUserAttribute(): ?User
    {
        if ($user = $this->discord->users->get('id', $this->user_id)) {
            return $user;
        }

        if ($this->attributes['member']->user ?? null) {
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
    
    /**
     * Gets the request_to_speak_timestamp attribute.
     *
     * @return Carbon
     */
    protected function getRequestToSpeakTimestampAttribute(): ?Carbon
    {
        if (($this->attributes['request_to_speak_timestamp'] ?? null) != null) {
            return new Carbon($this->attributes['request_to_speak_timestamp']);
        }

        return null;
    }
}
