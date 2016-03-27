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
 * Event that is emitted when `CHANNEL_DELETE` is fired.
 */
class ChannelDelete extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Channel The parsed data.
     */
    public function getData($data, $discord)
    {
        return $this->partFactory->create(Channel::class, $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::remove("channel.{$data->id}");

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->channels as $cindex => $channel) {
                    if ($channel->id == $data->id) {
                        $guild->channels->pull($index);

                        return $discord;
                    }
                }
            }
        }

        return $discord;
    }
}
