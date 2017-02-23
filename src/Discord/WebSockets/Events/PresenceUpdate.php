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

use Discord\Parts\WebSockets\PresenceUpdate as PresenceUpdatePart;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class PresenceUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $presenceUpdate = $this->factory->create(PresenceUpdatePart::class, $data, true);
        $old            = null;

        if ($this->discord->options['storeMembers'] && $this->discord->guilds->has($presenceUpdate->guild_id)) {
            $guild = $this->discord->guilds->offsetGet($presenceUpdate->guild_id);

            if ($guild->members->has($presenceUpdate->user->id)) {
                $member = $guild->members->offsetGet($presenceUpdate->user->id);
                $rawOld = array_merge([
                    'roles'  => [],
                    'status' => null,
                    'game'   => null,
                    'nick'   => null,
                ], $member->getRawAttributes());

                $old = $this->factory->create(PresenceUpdatePart::class, [
                    'user'     => $member->user,
                    'roles'    => $rawOld['roles'],
                    'guild_id' => $presenceUpdate->guild_id,
                    'status'   => $rawOld['status'],
                    'game'     => $rawOld['game'],
                    'nick'     => $rawOld['nick'],
                ], true);

                $presenceAttributes = $presenceUpdate->getRawAttributes();
                $member->fill([
                    'status' => $presenceAttributes['status'],
                    'roles'  => $presenceAttributes['roles'],
                    'nick'   => isset($presenceAttributes['nick']) ? $presenceAttributes['nick'] : null,
                    'game'   => $presenceAttributes['game'],
                ]);

                $guild->members->offsetSet($member->id, $member);
            }
        }

        $deferred->resolve([$presenceUpdate, $old]);
    }
}
