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
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_BAN_REMOVE` is fired.
 */
class GuildBanRemove extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Ban The parsed data.
     */
    public function getData($data, $discord)
    {
        $guild = $discord->guilds->get('id', $data->guild_id);

        if (is_null($guild)) {
            $guild = new Guild(['id' => $data->guild_id, 'name' => 'Unknown'], true);
        }

        return new Ban([
            'guild' => $guild,
            'user'  => $data->user,
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::delete("guild.{$data->guild_id}.bans.{$data->user_id}");

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->bans as $bindex => $ban) {
                    if ($ban->user_id == $data->user_id) {
                        $guild->bans->pull($bindex);

                        break;
                    }
                }

                break;
            }
        }

        return $discord;
    }
}
