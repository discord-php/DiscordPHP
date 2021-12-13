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
use Discord\Parts\Channel\Sticker;

class GuildStickersUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $adata = (array) $data->stickers;
        $adata['guild_id'] = $data->guild_id;

        $stickerPart = $this->factory->create(Sticker::class, $adata, true);

        if ($guild = $this->discord->guilds->get('id', $stickerPart->guild_id)) {
            $old = $guild->stickers->get('id', $stickerPart->id);
            $guild->stickers->push($stickerPart);

            $this->discord->guilds->push($guild);
        } else {
            $old = null;
        }

        $deferred->resolve([$stickerPart, $old]);
    }
}
