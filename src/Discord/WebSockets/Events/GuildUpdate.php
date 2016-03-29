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
use React\Promise\Deferred;

/**
 * Event that is emitted when `GUILD_UPDATE` is fired.
 */
class GuildUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, array $data)
    {
        $data = $this->partFactory->create(Guild::class, $data);

        $this->cache->set("guild.{$data->id}", $data);

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->id) {
                $this->discord->guilds[$index] = $guild;

                break;
            }
        }

        $deferred->resolve($data);
    }
}
