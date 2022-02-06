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

use Discord\Parts\WebSockets\PresenceUpdate as PresenceUpdatePart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#presence-update
 */
class PresenceUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /**
         * @var PresenceUpdatePart
         */
        $presence = $this->factory->create(PresenceUpdatePart::class, $data, true);

        if ($guild = $presence->guild) {
            if ($member = $presence->member) {
                $oldPresence = $member->updateFromPresence($presence);

                $guild->members->offsetSet($member->id, $member);
                $this->discord->guilds->offsetSet($guild->id, $guild);

                $deferred->resolve([$presence, $oldPresence]);
            }
        }

        $deferred->resolve($presence);
    }
}
