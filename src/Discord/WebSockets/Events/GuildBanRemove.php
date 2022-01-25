<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Guild\Ban;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\User\User;

class GuildBanRemove extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $ban = $this->factory->create(Ban::class, $data, true);

        if ($guild = $ban->guild) {
            $guild->bans->pull($ban->user->id);
            $this->discord->guilds->push($guild);
        }

        // User caching
        if ($user = $this->discord->users->get('id', $data->user->id)) {
            $user->fill((array) $data->user);
        } else {
            $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->user, true));
        }

        $deferred->resolve($ban);
    }
}
