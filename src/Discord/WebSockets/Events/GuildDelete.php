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

use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-delete
 */
class GuildDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if (! $guildPart = $this->discord->guilds->pull($data->id)) {
            /** @var Guild */
            $guildPart = $this->factory->create(Guild::class, $data);
        }

        $deferred->resolve([$guildPart, $data->unavailable ?? false]);
    }
}
