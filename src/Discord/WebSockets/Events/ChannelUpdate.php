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
use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `CHANNEL_UPDATE` is fired.
 */
class ChannelUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Channel The parsed data.
     */
    public function getData($data, $discord)
    {
        return new Channel((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("channel.{$data->id}", $data);

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->channels as $cindex => $channel) {
                    if ($channel->id == $data->id) {
                        $guild->channels[$cindex] = $data;

                        break;
                    }
                }
            }
        }

        return $discord;
    }
}
