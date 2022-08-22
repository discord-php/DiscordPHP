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
use Discord\Helpers\Deferred;

/**
 * @link https://discord.com/developers/docs/topics/gateway#guild-delete
 *
 * @since 2.1.3
 *
 * @todo update params in docs
 */
class GuildDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $deferred->resolve($this->discord->guilds->cachePull($data->id, $data)->then(fn ($guildPart) => [$guildPart, $data->unavailable ?? false]));
    }
}
