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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;

/**
 * The payload used to trigger the initial handshake with the Gateway.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#identify
 *
 * @since 10.55.1
 *
 * @property string      $token           Authentication token.
 * @property array       $properties      Connection properties containing `os`, `browser`, and `device`.
 * @property ?bool|null  $compress        Whether this connection supports packet compression.
 * @property ?int|null   $large_threshold Number of members where the Gateway stops sending offline members.
 * @property ?array|null $shard           Shard information as `[shard_id, num_shards]`.
 * @property ?array|null $presence        Initial presence information.
 * @property int         $intents         Gateway intents to receive.
 * @property ?int|null   $capabilities    Gateway capability flags.
 */
class Identify extends Part
{
    /** Opts the client into receiving obfuscated channel metadata. */
    public const FLAG_CHANNEL_OBFUSCATION = 1 << 15;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'token',
        'properties',
        'compress',
        'large_threshold',
        'shard',
        'presence',
        'intents',
        'capabilities',
    ];

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'token' => $this->token,
            'properties' => $this->properties,
            'intents' => $this->intents,
        ];

        if (isset($this->compress)) {
            $data['compress'] = $this->compress;
        }

        if (isset($this->large_threshold)) {
            $data['large_threshold'] = $this->large_threshold;
        }

        if (isset($this->shard)) {
            $data['shard'] = $this->shard;
        }

        if (isset($this->presence)) {
            $data['presence'] = $this->presence;
        }

        if (isset($this->capabilities)) {
            $data['capabilities'] = $this->capabilities;
        }

        return $data;
    }
}
