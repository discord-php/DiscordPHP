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
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-emojis-update
 *
 * @since 7.0.0
 */
class GuildEmojisUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldEmojis = Collection::for(Emoji::class);
        $emojiParts = Collection::for(Emoji::class);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            $oldEmojis->merge($guild->emojis);
            $guild->emojis->clear();
        }

        foreach ($data->emojis as &$emoji) {
            if (isset($emoji->user)) {
                // User caching from emoji uploader
                $this->cacheUser($emoji->user);
            } elseif ($oldEmoji = $oldEmojis->offsetGet($emoji->id)) {
                if ($uploader = $oldEmoji->user) {
                    $emoji->user = (object) $uploader->getRawAttributes();
                }
            }
            $emoji->guild_id = $data->guild_id;
            $emojiParts->pushItem($this->factory->part(Emoji::class, (array) $emoji, true));
        }

        if (isset($guild)) {
            yield $guild->emojis->cache->setMultiple($emojiParts->toArray());
        }

        return [$emojiParts, $oldEmojis];
    }
}
