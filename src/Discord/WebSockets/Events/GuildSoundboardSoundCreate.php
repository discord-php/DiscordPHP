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
use Discord\Repository\Guild\SoundRepository;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-create
 *
 * @since 10.0.0
 */
class GuildSoundboardSoundCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Sound */
        $part = $this->factory->part(Sound::class, (array) $data, true);

        /** @var Guild|null */
        $guild = yield $this->discord->guilds->cacheGet($data->guild_id);

        if (! $guild instanceof Guild) {
            return $part;
        }

        /** @var SoundRepository */
        if ($repository = $guild->sounds) {
            $repository->set($part->sound_id, $part);
        }

        return $part;
    }
}
