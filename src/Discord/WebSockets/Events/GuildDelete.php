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

use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-delete
 *
 * @since 2.1.3
 */
class GuildDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        return yield $this->discord->guilds->cachePull($data->id, $data)->then(fn ($guildPart) => [$guildPart, $data->unavailable ?? false]);
    }
}
