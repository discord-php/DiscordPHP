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

use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class GuildUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var \Discord\Parts\Guild\Guild */
        $guild = $this->discord->guilds->get('id', $data->id);
        $oldGuild = clone $guild;

        $guild->fill((array) $data);

        $deferred->resolve([$guild, $oldGuild]);
    }
}
