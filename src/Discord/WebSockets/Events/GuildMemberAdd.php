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
 * @link https://discord.com/developers/docs/topics/gateway#guild-member-add
 *
 * @since 2.1.3
 */
class GuildMemberAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            /** @var Member */
            $memberPart = $this->factory->create(Member::class, $data, true);

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                yield $guild->members->cache->set($data->user->id, $memberPart);
                ++$guild->member_count;
            }

            $this->cacheUser($data->user);

            return $memberPart;
        }, $data)->then([$deferred, 'resolve']);
    }
}
