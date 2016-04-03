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

use Discord\Cache\Cache;
use Discord\Helpers\Collection;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
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
        return new VoiceStateUpdatePart((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->members as $mindex => $member) {
                    if ($member->id == $data->user_id) {
                        $member->deaf = $data->deaf;
                        $member->mute = $data->mute;

                        $guild->members[$mindex] = $member;

                        break;
                    }
                }

                if (Cache::has("channels.{$data->channel_id}.voice.members")) {
                    $collection = Cache::get("channels.{$data->channel_id}.voice.members");
                } else {
                    $collection = new Collection();
                }

                $collection[$data->user_id] = $data;
                Cache::set("channels.{$data->channel_id}.voice.members", $collection);

                $discord->guilds[$index] = $guild;

                break;
            }
        }

        return $discord;
    }
}
