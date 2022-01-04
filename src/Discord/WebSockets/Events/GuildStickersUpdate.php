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
use Discord\Parts\Channel\Sticker;

class GuildStickersUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $stickerParts = Collection::for(Sticker::class);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $oldParts = clone $guild->stickers;
            $guild->stickers->clear();
        }

        foreach ($data->stickers as $sticker) {
            if (! isset($sticker->user) && $oldUser = $oldParts->offsetGet($sticker->id)->user) {
                $sticker->user = $oldUser;
            }
            $stickerPart = $this->factory->create(Sticker::class, $sticker, true);
            $guild->stickers->pushItem($stickerPart);
            $stickerParts->pushItem($stickerPart);
        }

        $deferred->resolve([$stickerParts, $oldParts]);
    }
}
