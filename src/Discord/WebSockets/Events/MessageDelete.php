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
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `MESSAGE_DELETE` is fired.
 */
class MessageDelete extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Message The parsed data.
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
        Cache::remove("message.{$data->id}");

        foreach ($discord->guilds as $index => $guild) {
            foreach ($guild->channels as $cindex => $channel) {
                if ($channel->id == $data->channel_id) {
                    $channel->messages->pull($data->id);

                    break;
                }
            }
        }

        return $discord;
    }
}
