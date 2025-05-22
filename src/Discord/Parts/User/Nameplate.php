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
 * Represents a user's nameplate collectible.
 *
 * @link https://discord.com/developers/docs/resources/user#nameplate-nameplate-structure
 *
 * @property string $sku_id   ID of the nameplate's decoration SKU.
 * @property string $asset    Path to the nameplate asset.
 * @property string $label    The label of this nameplate.
 * @property string $palette  The name of the most dominant colour in this nameplate.
 */
class Nameplate extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'sku_id',
        'asset',
        'label',
        'palette',
    ];
}
