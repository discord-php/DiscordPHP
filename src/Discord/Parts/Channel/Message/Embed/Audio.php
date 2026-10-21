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

namespace Discord\Parts\Channel\Message\Embed;

use Discord\Parts\Part;

/**
 * Audio metadata for an embed, defined in Discord's stable OpenAPI description.
 *
 * @link https://github.com/discord/discord-api-spec/blob/bf93257f85d62054a0a6982d32f6bcb724abd015/specs/openapi.json
 *
 * @since 10.69.0
 *
 * @property      string|null $url          The source URL of the audio.
 * @property-read string|null $proxy_url    Discord's proxied URL of the audio.
 * @property-read string|null $content_type The audio's media type.
 * @property      string|null $description  The description of the audio.
 */
class Audio extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'url',
        'proxy_url',
        'content_type',
        'description',
    ];

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        return $this->getRawAttributes();
    }
}
