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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->notify('Guild is unavailable.');

            return;
        }

        /**
         * @var $guildPart Guild
         */
        $guildPart = $this->factory->create(Guild::class, (array) $data, true);

        foreach ($data->roles as $role) {
            $role = (array) $role;
            $role['guild_id'] = $guildPart->id;
            $rolePart = $this->factory->create(Role::class, (array) $role, true);

            $guildPart->roles->push($rolePart);
        }

        if ($guildPart->large) {
            $this->discord->addLargeGuild($guildPart);
        }

        $old = $this->discord->guilds->get('id', $guildPart->id);
        $this->discord->guilds->offsetSet($guildPart->id, $guildPart);

        $deferred->resolve([$guildPart, $old]);
    }
}
