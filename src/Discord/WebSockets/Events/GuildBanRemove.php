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

use Discord\Parts\Guild\Ban;
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

        return new Ban([
            'guild' => $guild,
            'user' => $data->user,
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                foreach ($guild->bans as $bindex => $ban) {
                    if ($ban->user_id == $data->user_id) {
                        $guild->bans->pull($bindex);

                        break;
                    }
                }

                $discord->guilds->pull($index);
                $discord->guilds->push($guild);

                break;
            }
        }

        return $discord;
    }
}
