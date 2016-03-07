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
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_UPDATE` is fired.
 */
class GuildUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Guild The parsed data.
     */
    public function getData($data, $discord)
    {
        return new Guild((array) $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("guild.{$data->id}", $data);

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->id) {
                $discord->guilds[$index] = $guild;

                break;
            }
        }

        return $discord;
    }
}
