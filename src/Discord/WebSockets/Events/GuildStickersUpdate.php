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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Sticker;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-stickers-update
 *
 * @since 7.0.0
 */
class GuildStickersUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldStickers = Collection::for(Sticker::class);
        $stickerParts = Collection::for(Sticker::class);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            $oldStickers->merge($guild->stickers);
            $guild->stickers->clear();
        }

        foreach ($data->stickers as &$sticker) {
            if (isset($sticker->user)) {
                // User caching from sticker uploader
                $this->cacheUser($sticker->user);
            } elseif ($oldSticker = $oldStickers->offsetGet($sticker->id)) {
                if ($uploader = $oldSticker->user) {
                    $sticker->user = (object) $uploader->getRawAttributes();
                }
            }
            $stickerParts->pushItem($this->factory->part(Sticker::class, (array) $sticker, true));
        }

        if (isset($guild)) {
            yield $guild->stickers->cache->setMultiple($stickerParts->toArray());
        }

        return [$stickerParts, $oldStickers];
    }
}
