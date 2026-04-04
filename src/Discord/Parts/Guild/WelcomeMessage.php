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

namespace Discord\Parts\Guild;

use Discord\Parts\Part;

/**
 * Welcome message shown to new members.
 *
 * @link https://github.com/discord/discord-api-spec/blob/7cba79e03a393456fc904cff470097d3be383bec/specs/openapi_preview.json#L39934
 *
 * @since 10.47.0 OpenAPI Preview
 *
 * @property string[] $author_ids The IDs of the users who authored the welcome message (max 10).
 * @property string   $message    The welcome message shown to new members (max 300 characters).
 */
class WelcomeMessage extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'author_ids',
        'message',
    ];
}
