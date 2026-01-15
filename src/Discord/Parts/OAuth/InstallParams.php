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

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

/**
 * Settings for the app's default in-app authorization link, if enabled.
 *
 * @link https://discord.com/developers/docs/resources/application#install-params-object
 *
 * @since 10.24.0
 *
 * @property string[] $scopes      Scopes to add the application to the server with.
 * @property string   $permissions Permissions to request for the bot role.
 */
class InstallParams extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'scopes',
        'permissions',
    ];
}
