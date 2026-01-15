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
 * Represents the session description data.
 *
 * @since 10.41.0
 *
 * @property string $video_codec           The video codec used.
 * @property int    $secure_frames_version The secure frames version.
 * @property string $secret_key            The 32-byte secret key used for encryption.
 * @property string $mode                  The selected encryption mode.
 * @property string $media_session_id      The media session ID.
 * @property int    $dave_protocol_version The DAVE protocol version.
 * @property string $audio_codec           The audio codec used.
 */
class SessionDescription extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'video_codec',
        'secure_frames_version',
        'secret_key',
        'mode',
        'media_session_id',
        'dave_protocol_version',
        'audio_codec',
    ];

    /**
     * Get the secret key as a raw binary string.
     *
     * @return string The raw binary secret key.
     */
    public function getSecretKeyAttribute(): string
    {
        return pack('C*', ...$this->attributes['secret_key']);
    }

    public function __debugInfo(): array
    {
        $array = $this->jsonSerialize();

        if (isset($array['secret_key'])) {
            $array['secret_key'] = '*****';
        }

        return $array;
    }
}
