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
use Discord\Parts\Channel\Emoji;

class GuildEmojisUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $adata = (array) $data->emojis;
        $adata['guild_id'] = $data->guild_id;

        $emojiPart = $this->factory->create(Emoji::class, $adata, true);

        if ($guild = $this->discord->guilds->get('id', $emojiPart->guild_id)) {
            $old = $guild->emojis->get('id', $emojiPart->id);
            $guild->emojis->push($emojiPart);
        } else {
            $old = null;
        }

        $deferred->resolve([$emojiPart, $old]);
    }
}
