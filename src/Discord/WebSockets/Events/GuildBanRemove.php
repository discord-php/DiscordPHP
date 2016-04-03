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
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `GUILD_BAN_REMOVE` is fired.
 */
class GuildBanRemove extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);

        if (is_null($guild)) {
            $guild = $this->partFactory->create(Guild::class, ['id' => $data->guild_id, 'name' => 'Unknown'], true);
        }

        $data = $this->partFactory->create(Ban::class, ['guild' => $guild, 'user'  => $data->user], true);
        $this->cache->remove("guild.{$data->guild_id}.bans.{$data->user_id}");

        foreach ($this->discord->guilds as $index => $guild) {
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

        $deferred->resolve($data);
    }
}
