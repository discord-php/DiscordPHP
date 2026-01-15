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
 * integration account information.
 *
 * @link https://discord.com/developers/docs/resources/guild#integration-account-object
 *
 * @since 10.24.0
 *
 * @property string $id   The id of the account.
 * @property string $name The name of the account.
 */
class Account extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
    ];
}
