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
use Discord\Parts\Guild\Emoji;

class GuildEmojisUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $oldParts = Collection::for(Emoji::class);
        $emojiParts = Collection::for(Emoji::class);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $oldParts->merge($guild->emojis);
            $guild->emojis->clear();
        }

        foreach ($data->emojis as $emoji) {
            if (! isset($emoji->user) && $oldPart = $oldParts->offsetGet($emoji->id)) {
                $emoji->user = $oldPart->user;
            }
            $emojiPart = $this->factory->create(Emoji::class, $emoji, true);
            $emojiParts->pushItem($emojiPart);
        }

        if ($guild) {
            $guild->emojis->merge($emojiParts);
        }

        $deferred->resolve([$emojiParts, $oldParts]);
    }
}
