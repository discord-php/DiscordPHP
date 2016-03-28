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

use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;

/**
 * Event that is emitted when `GUILD_DELETE` is fired.
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
        $guildPart = $this->partFactory->create(Guild::class, $data, true);

        return $guildPart;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        $this->cache->remove("guild.{$data->id}");

        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->id) {
                $discord->guilds->pull($index);
            }
        }

        return $discord;
    }
}
