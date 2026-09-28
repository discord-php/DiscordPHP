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

namespace Discord\Repository;

use Discord\Parts\WebSockets\VoiceSession;

/**
 * The bot's voice sessions, one per guild it is connected to voice in.
 *
 * The client keeps these from the gateway: the bot's own `VOICE_STATE_UPDATE` records the session,
 * `VOICE_SERVER_UPDATE` the server, and leaving the voice channel removes it. They are stored through the
 * cache like every other repository, so a cache shared between shards or processes shares them too.
 *
 * There is nothing to fetch or save: Discord has no REST route for a voice session.
 *
 * @see VoiceSession
 * @see \Discord\Discord::$voice_sessions
 *
 * @since 10.66.0
 *
 * @method VoiceSession|null get(string $discrim, $key)
 * @method VoiceSession|null pull(string|int $key, $default = null)
 * @method VoiceSession|null first()
 * @method VoiceSession|null last()
 * @method VoiceSession|null find(callable $callback)
 */
class VoiceSessionRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $discrim = 'guild_id';

    /**
     * @inheritDoc
     */
    protected $endpoints = [];

    /**
     * @inheritDoc
     */
    protected $class = VoiceSession::class;
}
