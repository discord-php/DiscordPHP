<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * The data for the user's avatar decoration.
 *
 * @link https://discord.com/developers/docs/resources/user#avatar-decoration-data-object
 *
 * @property string $asset  The avatar decoration hash.
 * @property string $sku_id The id of the avatar decoration's SKU.
 */
class AvatarDecorationData extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'asset',
        'sku_id',
    ];
}
