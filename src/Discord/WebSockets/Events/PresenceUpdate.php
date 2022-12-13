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
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#presence-update
 *
 * @see \Discord\Parts\WebSockets\PresenceUpdate
 *
 * @since 2.1.3
 */
class PresenceUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var PresenceUpdatePart */
        $presence = $this->factory->part(PresenceUpdatePart::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Member */
            if ($member = yield $guild->members->cacheGet($data->user->id)) {
                $oldPresence = $member->updateFromPresence($presence);

                $guild->members->set($data->user->id, $member);

                return [$presence, $oldPresence];
            }
        }

        return $presence;
    }
}
