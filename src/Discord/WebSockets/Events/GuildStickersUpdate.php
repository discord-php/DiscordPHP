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

use Discord\Helpers\Collection;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\Guild\Sticker;

class GuildStickersUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $oldParts = Collection::for(Sticker::class);
        $stickerParts = Collection::for(Sticker::class);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $oldParts->merge($guild->stickers);
            $guild->stickers->clear();
        }

        foreach ($data->stickers as $sticker) {
            if (isset($sticker->user)) {
                // User caching from sticker uploader
                $this->cacheUser($sticker->user);
            } elseif($oldPart = $oldParts->offsetGet($sticker->id)) {
                $sticker->user = $oldPart->user;
            }
            $stickerPart = $this->factory->create(Sticker::class, $sticker, true);
            $stickerParts->pushItem($stickerPart);
        }

        if ($guild) {
            $guild->stickers->merge($stickerParts);
        }

        $deferred->resolve([$stickerParts, $oldParts]);
    }
}
