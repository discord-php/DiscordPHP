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

/**
 * Event that is emitted wheh `VOICE_STATE_UPDATE` is fired.
 */
class VoiceStateUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return array The data.
     */
    public function getData($data, $discord)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $member = @$guild->members[$data->user_id];

                if (is_null($member)) {
                    break;
                }

                $member->deaf = $data->deaf;
                $member->mute = $data->mute;

                $guild->members[$data->user_id] = $member;

                break;
            }
        }

        return $discord;
    }
}
