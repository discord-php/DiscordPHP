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
use React\Promise\Deferred;

class GuildBanAdd extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);
        $ban   = $this->factory->create(Ban::class, [
            'guild' => $guild,
            'user'  => $data->user,
        ], true);

        $guild = $this->discord->guilds->get('id', $ban->guild->id);
        $guild->bans->push($ban);

        $deferred->resolve($ban);
    }
}
