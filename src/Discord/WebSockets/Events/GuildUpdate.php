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
use Discord\Parts\Guild\Guild;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-update
 */
class GuildUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $oldGuild = null;

        if ($guildPart = $this->discord->guilds->get('id', $data->id)) {
            $oldGuild = clone $guildPart;
            $guildPart->fill((array) $data);
        } else {
            /** @var Guild */
            $guildPart = $this->factory->create(Guild::class, $data, true);
            $this->discord->guilds->pushItem($guildPart);
        }

        $deferred->resolve([$guildPart, $oldGuild]);
    }
}
