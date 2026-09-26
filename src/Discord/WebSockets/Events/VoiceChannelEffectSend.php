<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\VoiceChannelEffect;
use Discord\WebSockets\Event;

/**
 * Someone sent an emoji reaction or a soundboard sound in a voice channel the bot is connected to. Needs
 * the `GUILD_VOICE_STATES` intent.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#voice-channel-effect-send
 *
 * @since 10.60.0
 */
class VoiceChannelEffectSend extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        return $this->factory->part(VoiceChannelEffect::class, (array) $data, true);
    }
}
