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

use Discord\Parts\WebSockets\VoiceServerUpdate as VoiceServerUpdatePart;
use Discord\Parts\WebSockets\VoiceSession;
use Discord\WebSockets\Event;

/**
 * @link https://docs.discord.com/developers/events/gateway-events#voice-server-update
 *
 * @see \Discord\Parts\WebSockets\VoiceServerUpdate
 *
 * @since 4.0.0
 */
class VoiceServerUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var VoiceServerUpdatePart */
        $serverPart = $this->factory->part(VoiceServerUpdatePart::class, (array) $data, true);

        if (isset($data->guild_id)) {
            // The server's half of the bot's voice session in the guild. The session's half comes in the bot's
            // own VOICE_STATE_UPDATE, which may arrive before or after this.
            $sessions = $this->discord->voice_sessions;

            /** @var ?VoiceSession */
            $session = yield $sessions->cacheGet($data->guild_id);
            $session ??= $this->factory->part(VoiceSession::class, ['guild_id' => $data->guild_id], true);
            $session->fill([
                'token' => $data->token ?? null,
                'endpoint' => $data->endpoint ?? null,
            ]);

            yield $sessions->cache->set($data->guild_id, $session);
        }

        return $serverPart;
    }
}
