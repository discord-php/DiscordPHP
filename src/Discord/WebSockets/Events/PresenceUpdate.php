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

        $guild  = $this->discord->guilds->get('id', $presenceUpdate->guild_id);
        $member = $guild->members->get('id', $presenceUpdate->user->id);

        if (! is_null($member)) {
            $rawOld = array_merge([
                'roles'  => [],
                'status' => null,
                'game'   => null,
                'nick'   => null,
            ], $member->getRawAttributes());

            $old = $this->factory->create(PresenceUpdatePart::class, [
                'user'     => $this->discord->users->get('id', $presenceUpdate->user->id),
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
                'nick'   => $presenceAttributes['nick'],
                'game'   => $presenceAttributes['game'],
            ]);

            $guild->members->push($member);
        }

        $deferred->resolve([$presenceUpdate, $old]);
    }
}
