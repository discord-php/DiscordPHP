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
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `MESSAGE_UPDATE` is fired.
 */
class MessageUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Message The parsed data.
     */
    public function getData($data, $discord)
    {
        return new Message((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("message.{$data->id}", $data);

        foreach ($discord->guilds as $index => $guild) {
            foreach ($guild->channels as $cindex => $channel) {
                if ($channel->id == $data->channel_id) {
                    $message = $channel->messages->pull($data->id);

                    if (! isset($data->content)) {
                        $channel->messages->push(new Message(array_merge((array) $message, (array) $data), true));
                    } else {
                        $channel->messages->push($data);
                    }

                    break;
                }
            }

            $discord->guilds[$index] = $guild;
        }

        return $discord;
    }
}
