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

namespace Discord\Parts\WebSockets;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * The bot's voice connection in one guild, as the gateway describes it.
 *
 * Connecting to voice takes two gateway events: the bot's own `VOICE_STATE_UPDATE` gives the session, and
 * `VOICE_SERVER_UPDATE` gives the server and a token for it. The client keeps both here, one per guild, in
 * {@see \Discord\Discord::$voice_sessions}: added when the bot joins a voice channel, and removed when it
 * leaves.
 *
 * Voice sessions are stored through the cache like every other repository's parts, so a cache shared
 * between shards or processes shares them too. The token is kept out of `jsonSerialize()` and debug output,
 * but it is stored in the cache: resuming the voice connection needs it.
 *
 * @link https://docs.discord.com/developers/topics/voice-connections#retrieving-voice-server-information
 *
 * @since 10.66.0
 *
 * @property      string        $guild_id   ID of the guild the voice connection is in.
 * @property-read Guild|null    $guild      The guild the voice connection is in.
 * @property      ?string|null  $channel_id ID of the voice channel the bot is in.
 * @property-read ?Channel|null $channel    The voice channel the bot is in.
 * @property      ?string|null  $user_id    ID of the bot's user.
 * @property      ?string|null  $session_id The voice session ID, from the bot's `VOICE_STATE_UPDATE`. Null once the voice client finds the session can no longer be resumed.
 * @property      ?string|null  $token      The voice token, from the last `VOICE_SERVER_UPDATE`.
 * @property      ?string|null  $endpoint   The voice server's host, from the last `VOICE_SERVER_UPDATE`. Null while Discord reallocates the server.
 */
class VoiceSession extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'user_id',
        'session_id',
        'token',
        'endpoint',
    ];

    /**
     * @inheritDoc
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Whether the voice gateway connection could be resumed with this session: it has a session, a token and
     * a server to resume it on.
     *
     * @link https://docs.discord.com/developers/topics/voice-connections#resuming-voice-connection
     */
    public function isResumable(): bool
    {
        return isset($this->attributes['session_id'], $this->attributes['token'], $this->attributes['endpoint']);
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null The guild the voice connection is in.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null The voice channel the bot is in.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        if ($guild = $this->guild) {
            return $guild->channels->get('id', $this->attributes['channel_id']);
        }

        return $this->discord->getChannel($this->attributes['channel_id']);
    }

    /**
     * Gets the string representation of the voice session.
     *
     * @return string The session ID as a string.
     *
     * @since 10.66.1
     */
    public function __toString(): string
    {
        return (string) $this->session_id;
    }
}
