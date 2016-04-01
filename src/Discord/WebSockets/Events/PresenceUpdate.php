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

/**
 * Event that is emitted when `PRESENCE_UPDATE` is fired.
 */
class PresenceUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, array $data)
    {
        $data = $this->partFactory->create(PresenceUpdatePart::class, $data, true);

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $member = array_key_exists($data->user->id, $guild->members) ? $guild->members[$data->user->id] : null;
                if (! is_null($member)) {
                    $member->game   = $data->game;
                    $member->status = $data->status;

                    $guild->members[$data->user->id] = $member;
                }

                break;
            }
        }

        $deferred->resolve($data);
    }
}
