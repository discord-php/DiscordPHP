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

use Discord\Parts\Guild\Role;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-role-create
 */
class GuildRoleCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            /** @var Role */
            $rolePart = $this->factory->part(Role::class, (array) $data->role + ['guild_id' => $data->guild_id], true);

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                yield $guild->roles->cache->set($data->role->id, $rolePart);
            }

            return $rolePart;
        }, $data)->then([$deferred, 'resolve']);
    }
}
