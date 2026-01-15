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

namespace Discord\Voice;

use Discord\Parts\Part;

/**
 * Represents the ready data.
 *
 * @link https://discord.com/developers/docs/topics/voice-connections#establishing-a-voice-websocket-connection-example-voice-ready-payload
 *
 * @since 10.41.0
 *
 * @property array    $streams     The streams information.
 * @property int      $ssrc        The SSRC identifier.
 * @property int      $port        The port number.
 * @property string[] $modes       The supported encryption modes.
 * @property string   $ip          The IP address.
 * @property string[] $experiments The list of experiments.
 *
 * @property-read string $type     The type of the stream.
 * @property-read int    $ssrc     The SSRC of the stream.
 * @property-read int    $rtx_ssrc The RTX SSRC of the stream.
 * @property-read string $rid      The RID of the stream.
 * @property-read int    $quality  The quality of the stream.
 * @property-read bool   $active   Whether the stream is active.
 */
class Ready extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'streams',
        'ssrc',
        'port',
        'modes',
        'ip',
        'experiments',
    ];

    /**
     * Get the type of the stream.
     *
     * @return string The type of the stream.
     */
    public function getTypeAttribute(): string
    {
        return $this->attributes['streams'][0]['type'];
    }

    /**
     * Get the SSRC of the stream.
     *
     * @return int The SSRC of the stream.
     */
    public function getSsrcAttribute(): int
    {
        return $this->attributes['streams'][0]['ssrc'];
    }

    /**
     * Get the RTX SSRC of the stream.
     *
     * @return int The RTX SSRC of the stream.
     */
    public function getRtxSsrcAttribute(): int
    {
        return $this->attributes['streams'][0]['rtx_ssrc'];
    }

    /**
     * Get the RID of the stream.
     *
     * @return string The RID of the stream.
     */
    public function getRidAttribute(): string
    {
        return $this->attributes['streams'][0]['rid'];
    }

    /**
     * Get the quality of the stream.
     *
     * @return int The quality of the stream.
     */
    public function getQualityAttribute(): int
    {
        return $this->attributes['streams'][0]['quality'];
    }

    /**
     * Get whether the stream is active.
     *
     * @return bool Whether the stream is active.
     */
    public function getActiveAttribute(): bool
    {
        return $this->attributes['streams'][0]['active'];
    }
}
