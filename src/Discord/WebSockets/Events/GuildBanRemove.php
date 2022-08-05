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

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-ban-remove
 */
class GuildBanRemove extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $banPart = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($banPart = $guild->bans->pull($data->user->id)) {
                $banPart->fill((array) $data);
                $banPart->created = false;
            }
        }

        if (! $banPart) {
            /** @var Ban */
            $banPart = $this->factory->create(Ban::class, $data);
        }

        $this->cacheUser($data->user);

        $deferred->resolve($banPart);
    }
}
