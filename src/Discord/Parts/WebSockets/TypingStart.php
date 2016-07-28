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

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * A TypingStart part is used when the `TYPING_START` event is fired on the WebSocket. It contains
 * information such as when the event was fired and then channel it was fired in.
 *
 * @property \Discord\Parts\User\User       $user       The user that started typing.
 * @property string                         $user_id    The unique identifier of the user that started typing
 * @property Carbon                         $timestamp  A timestamp of when the user started typing.
 * @property \Discord\Parts\Channel\Channel $channel    The channel that the user started typing in.
 * @property string                         $channel_id The unique identifier of the channel that the user started typing in.
 */
class TypingStart extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['user_id', 'timestamp', 'channel_id'];

    /**
     * Gets the user attribute.
     *
     * @return User The user that started typing.
     */
    public function getUserAttribute()
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Gets the timestamp attribute.
     *
     * @return Carbon The time that the user started typing.
     */
    public function getTimestampAttribute()
    {
        return new Carbon(gmdate('r', $this->attributes['timestamp']));
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel The channel that the user started typing in.
     */
    public function getChannelAttribute()
    {
        foreach ($this->discord->guilds as $guild) {
            if ($guild->channels->has($this->channel_id)) {
                return $guild->channels->get('id', $this->channel_id);
            }
        }
    }
}
