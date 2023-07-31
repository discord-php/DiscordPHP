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
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-update
 *
 * @since 2.1.3
 */
class GuildUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldGuild = null;

        /** @var ?Guild */
        if ($guildPart = yield $this->discord->guilds->cacheGet($data->id)) {
            $oldGuild = clone $guildPart;
            $guildPart->fill((array) $data);
        } else {
            /** @var Guild */
            $guildPart = $this->discord->guilds->create($data, true);
        }

        $this->discord->guilds->set($data->id, $guildPart);

        return [$guildPart, $oldGuild];
    }
}
