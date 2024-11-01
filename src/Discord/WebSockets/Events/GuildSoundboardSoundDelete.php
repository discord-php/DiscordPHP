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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Sound;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-delete
 *
 * @since 10.0.0
 */
class GuildSoundboardSoundDelete extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $part = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Sound */
            $part = yield $guild->sounds->cachePull($data->sound_id);
            if ($part instanceof Sound) {
                $part->fill((array) $data);
                $part->created = false;
            }
        }

        return $part ?? $this->factory->part(Sound::class, (array) $data);
    }
}
