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

use Discord\Parts\Part;
use Discord\Parts\User\Member;

/**
 * Notifies the client of voice state updates about users.
 *
 * @property \Discord\Parts\Channel\Channel $channel    The channel that was affected.
 * @property string                         $channel_id The unique identifier of the channel that was affected.
 * @property bool                           $deaf       Whether the user is deaf.
 * @property \Discord\Parts\Guild\Guild     $guild      The guild that was affected.
 * @property string                         $guild_id   The unique identifier of the guild that was affected.
 * @property bool                           $mute       Whether the user is mute.
 * @property bool                           $self_deaf  Whether the user is self deafened.
 * @property bool                           $self_mute  Whether the user is self muted.
 * @property string                         $session_id The session ID for the voice session.
 * @property string                         $supress    Whether the user is muted by the current user.
 * @property string                         $user_id    The user that is affected by this voice state update.
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
        'user_id',
    ];

    /**
     * Gets the id attribute.
     *
     * @return id The member id.
     */
    public function getIdAttribute()
    {
        return $this->user_id;
    }

    /**
     * Gets the member attribute.
     *
     * @return Member|null The member attribute.
     */
    public function getMemberAttribute()
    {
        $guild = $this->discord->guilds->get('id', $this->guild_id);

        return $guild->members->get('id', $this->user_id);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null The channel attribute.
     */
    public function getChannelAttribute()
    {
        return $this->guild->channels->get('id', $this->channel_id);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}
