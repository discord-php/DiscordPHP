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
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;

use function React\Async\coroutine;

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
        coroutine(function ($data) {
            /** @var PresenceUpdatePart */
            $presence = $this->factory->create(PresenceUpdatePart::class, $data, true);

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Member */
                if ($member = yield $guild->members->cacheGet($data->user->id)) {
                    $oldPresence = $member->updateFromPresence($presence);

                    yield $guild->members->cache->set($data->user->id, $member);

                    return [$presence, $oldPresence];
                }
            }

            return $presence;
        }, $data)->then([$deferred, 'resolve']);
    }
}
