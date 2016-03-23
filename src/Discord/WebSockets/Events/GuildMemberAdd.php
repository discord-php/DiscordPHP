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
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_MEMBER_ADD` is fired.
 */
class GuildMemberAdd extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Member The parsed data.
     */
    public function getData($data, $discord)
    {
        return new Member((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("guild.{$data->guild_id}.members.{$data->id}", $data);

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $guild->members->push($data);
                ++$guild->member_count;

                break;
            }
        }

        return $discord;
    }
}
