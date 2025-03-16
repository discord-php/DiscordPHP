<?php

declare(strict_types=1);

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
use Discord\Repository\Guild\SoundRepository;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sounds-update
 *
 * @since 10.0.0
 */
class GuildSoundboardSoundsUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Guild|null */
        $guild = yield $this->discord->guilds->cacheGet($data->guild_id);

        if (! $guild instanceof Guild) {
            return null;
        }

        /** @var SoundRepository */
        $repository = $guild->sounds;

        foreach ($data as $soundData) {
            /** @var SoundboardSound */
            $part = $this->factory->part(Sound::class, (array) $soundData, true);
            $repository->set($part->sound_id, $part);
        }

        return $repository;
    }
}
