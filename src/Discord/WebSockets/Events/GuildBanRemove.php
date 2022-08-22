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

use Discord\Parts\Guild\Ban;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

use function React\Async\coroutine;

/**
 * @link https://discord.com/developers/docs/topics/gateway#guild-ban-remove
 */
class GuildBanRemove extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $banPart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?Ban */
                if ($banPart = yield $guild->bans->cachePull($data->user->id)) {
                    $banPart->fill((array) $data);
                    $banPart->created = false;
                }
            }

            $this->cacheUser($data->user);

            return $banPart ?? $this->factory->create(Ban::class, $data);
        }, $data)->then([$deferred, 'resolve']);
    }
}
