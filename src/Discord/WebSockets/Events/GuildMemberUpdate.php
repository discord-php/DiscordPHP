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

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-member-update
 */
class GuildMemberUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $memberPart = $oldMember = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Member */
                if ($oldMember = $guild->members[$data->user->id]) {
                    // Swap
                    $memberPart = $oldMember;
                    $oldMember = clone $oldMember;

                    $memberPart->fill((array) $data);
                }
            }

            if (! $memberPart) {
                /** @var Member */
                $memberPart = $this->factory->create(Member::class, $data, true);
                if (isset($guild) || $guild = $memberPart->guild) {
                    yield $guild->members->cache->set($data->user->id, $memberPart);
                }
            }

            $this->cacheUser($data->user);

            return [$memberPart, $oldMember];
        }, $data)->then([$deferred, 'resolve']);
    }
}
