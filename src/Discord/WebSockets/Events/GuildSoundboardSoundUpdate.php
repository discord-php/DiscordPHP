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
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-soundboard-sound-update
 *
 * @since 10.0.0
 */
class GuildSoundboardSoundUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $newPart = $oldPart = null;

        /** @var Guild|null */
        $guild = yield $this->discord->guilds->cacheGet($data->guild_id);
        if (! $guild instanceof Guild) {
            /** @var Sound */
            $newPart = $this->factory->part(Sound::class, (array) $data, true);
            return [$newPart, $oldPart];
        }

        /** @var ?Sound */
        $oldPart = yield $guild->sounds->cacheGet($data->sound_id);
        if ($oldPart instanceof Sound) {
            $newPart = clone $oldPart;
            $newPart->fill((array) $data);
        } else {
            /** @var Sound */
            $newPart = $this->factory->part(Sound::class, (array) $data, true);
        }

        /** @var SoundRepository */
        if ($repository = $guild->sounds) {
            $repository->set($newPart->sound_id, $newPart);
        }

        return [$newPart, $oldPart];
    }
}
