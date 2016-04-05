<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;

/**
 * Event that is emitted wheh `VOICE_STATE_UPDATE` is fired.
 */
class VoiceStateUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return VoiceStateUpdatePart The voice state.
     */
    public function getData($data, $discord)
    {
        return new VoiceStateUpdatePart((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        /*
        VOICE_STATE_UPDATE

        Cases:
        - User switches from guild A channel 1 to guild A channel 2
            - Remove the state from guild A channel 1
            - Add the state to guild A channel 2
        - User switches from guild A channel 1 to guild B channel 1
            - User is a bot:
                - Add the state to guild B channel 1
            - User is not a bot:
                - Remove the state from guild A channel 1
                - Add the state to guild B channel 1
        - User leaves guild A channel 1
            - Remove the state from guild A channel 1
         */

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->channels as $cindex => $channel) {
                    if (isset($channel->members[$data->user_id])) {
                        unset($channel->members[$data->user_id]);
                    }

                    if ($channel->id == $data->channel_id) {
                        $channel->members[$data->user_id] = $data;
                    }
                }
            } else {
                foreach ($guild->channels as $cindex => $channel) {
                    if (isset($channel->members[$data->user_id]) && ! $discord->bot) {
                        unset($channel->members[$data->user_id]);
                    }
                }
            }
        }

        return $discord;
    }
}
