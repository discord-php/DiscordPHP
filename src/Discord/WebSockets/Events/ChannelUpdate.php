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

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;

class ChannelUpdate extends Event
{
    /**
     * Returns the formatted data.
     *
     * @param array   $data
     * @param Discord $discord
     *
     * @return Message
     */
    public function getData($data, $discord)
    {
        return new Channel((array) $data, true);
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed   $data
     * @param Discord $discord
     *
     * @return Discord
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->channels as $cindex => $channel) {
                    if ($channel->id == $data->id) {
                        $guild->channels->pull($cindex);
                        $guild->channels->push($data);

                        break;
                    }
                }
            }
        }

        return $discord;
    }
}
