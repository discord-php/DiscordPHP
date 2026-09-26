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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\WebSockets\Op;

/**
 * A request the bot sent over the gateway that Discord rate limited, from the `RATE_LIMITED` event: which
 * opcode, how long to wait before sending it again, and what the request was for.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#rate-limited
 *
 * @since 10.60.0
 *
 * @property      int         $opcode      The request's gateway opcode, such as {@see Op::OP_REQUEST_GUILD_MEMBERS}.
 * @property      float       $retry_after How many seconds to wait before sending it again.
 * @property      object      $meta        What the request was for; for Request Guild Members, its `guild_id` and `nonce`.
 * @property-read string|null $guild_id    The guild whose members were requested.
 * @property-read string|null $nonce       The nonce the members were requested with.
 * @property-read Guild|null  $guild       The guild whose members were requested.
 */
class RateLimited extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'opcode',
        'retry_after',
        'meta',
    ];

    /**
     * Returns the ID of the guild whose members were requested.
     *
     * @return string|null
     */
    protected function getGuildIdAttribute(): ?string
    {
        return $this->metaValue('guild_id');
    }

    /**
     * Returns the nonce the members were requested with.
     *
     * @return string|null
     */
    protected function getNonceAttribute(): ?string
    {
        return $this->metaValue('nonce');
    }

    /**
     * Returns the guild whose members were requested.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (null === $guild_id = $this->guild_id) {
            return null;
        }

        return $this->discord->guilds->get('id', $guild_id);
    }

    /**
     * A field of the metadata, as a string.
     */
    private function metaValue(string $field): ?string
    {
        $value = ((array) ($this->attributes['meta'] ?? []))[$field] ?? null;

        return null === $value ? null : (string) $value;
    }
}
