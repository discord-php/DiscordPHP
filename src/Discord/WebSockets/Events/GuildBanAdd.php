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
 * Event that is emitted wheh `GUILD_BAN_ADD` is fired.
 */
class GuildBanAdd extends Event
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
            if ($guild->id == $data->guild_id && ! is_bool($guild->bans)) {
                $guild->bans->push($data);

                foreach ($guild->members as $mindex => $member) {
                    if ($member->id == $data->user_id) {
                        $guild->members->pull($mindex);
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
