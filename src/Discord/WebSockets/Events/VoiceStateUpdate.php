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
     * @return array The data.
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
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                if ($data->channel) {
                    $data->channel->members[$data->user_id] = $data;
                }

                $member = @$guild->members[$data->user_id];

                if (is_null($member)) {
                    continue;
                }

                $member->deaf = $data->deaf;
                $member->mute = $data->mute;

                $guild->members[$data->user_id] = $member;

                foreach ($guild->channels->getAll('type', 'voice') as $cindex => $channel) {
                    if ($channel->id == $data->channel->id) {
                        continue;
                    }

                    $channel->members->pull($data->user_id);
                }
            } else {
                foreach ($guild->channels->getAll('type', 'voice') as $cindex => $channel) {
                    $channel->members->pull($data->user_id);
                }
            }
        }

        return $discord;
    }
}
