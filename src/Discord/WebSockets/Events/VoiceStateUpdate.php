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

use Discord\Parts\Guild\Guild;
use Discord\Parts\WebSockets\VoiceSession;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;

/**
 * @link https://docs.discord.com/developers/events/gateway-events#voice-state-update
 *
 * @see \Discord\Parts\WebSockets\VoiceStateUpdate
 *
 * @since 2.1.3
 */
class VoiceStateUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldVoiceState = null;
        /** @var VoiceStateUpdatePart */
        $statePart = $this->factory->part(VoiceStateUpdatePart::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var Guild $guild */
            if (isset($data->member)) {
                $this->cacheMember($guild->members, (array) $data->member);
                $this->cacheUser($data->member->user);
            }

            $oldVoiceState = yield $guild->voice_states->cacheGet($data->user_id);

            yield $guild->voice_states->cache->set($data->user_id, $statePart);
        }

        yield from $this->updateVoiceSession($data);

        return [$statePart, $oldVoiceState];
    }

    /**
     * Records the bot's own voice session in the guild, or removes it when the bot has left voice there.
     * Other users' voice states are not the bot's sessions.
     *
     * Done whether or not the guild is cached: the voice client needs the session either way.
     *
     * @param object $data The voice state payload.
     *
     * @since 10.66.0
     */
    protected function updateVoiceSession(object $data): \Generator
    {
        if (! isset($data->guild_id) || (string) $data->user_id !== (string) $this->discord->id) {
            return;
        }

        $sessions = $this->discord->voice_sessions;

        if (! isset($data->channel_id)) {
            yield $sessions->cache->delete($data->guild_id);

            return;
        }

        /** @var ?VoiceSession */
        $session = yield $sessions->cacheGet($data->guild_id);
        $session ??= $this->factory->part(VoiceSession::class, ['guild_id' => $data->guild_id], true);
        $session->fill([
            'channel_id' => $data->channel_id,
            'user_id' => $data->user_id,
            'session_id' => $data->session_id ?? null,
        ]);

        yield $sessions->cache->set($data->guild_id, $session);
    }
}
