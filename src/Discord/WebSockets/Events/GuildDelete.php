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
 * Event that is emitted wheh `GUILD_DELETE` is fired.
 */
class GuildDelete extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Guild The parsed data.
     */
    public function getData($data, $discord)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $this->emit('unavailable', [$data->id]);
        }

        $guildPart = new Guild((array) $data, true);

        return $guildPart;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::remove("guild.{$data->id}");

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->id) {
                $discord->guilds->pull($index);
            }
        }

        return $discord;
    }
}
