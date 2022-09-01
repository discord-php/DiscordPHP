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
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway#guild-ban-add
 *
 * @since 2.1.3
 */
class GuildBanAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle($data)
    {
        /** @var Ban */
        $banPart = $this->factory->part(Ban::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            yield $guild->bans->cache->set($data->user->id, $banPart);
        }

        $this->cacheUser($data->user);

        return $banPart;
    }
}
