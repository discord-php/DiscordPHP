<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Ban;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class GuildBanAdd extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $ban = $this->factory->create(Ban::class, $data, true);

        if ($guild = $ban->guild) {
            $guild->bans->push($ban);
            $this->discord->guilds->push($guild);
        }

        $deferred->resolve($ban);
    }
}
