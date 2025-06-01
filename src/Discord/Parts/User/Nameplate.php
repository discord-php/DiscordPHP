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
 * @since 10.10.0
 *
 * @property      string       $asset      Path to the nameplate asset.
 * @property      ?string|null $expires_at The date and time when the nameplate expires.
 * @property      ?string|null $label      The label of this nameplate.
 * @property      string       $palette    The name of the most dominant colour in this nameplate.
 * @property      string       $sku_id     ID of the nameplate's decoration SKU.
 *
 * @property-read string  $id The identifier of the nameplate.
 */
class Nameplate extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id', // @internal
        'sku_id',
        'asset',
        'label',
        'palette',
        'expires_at',
    ];

    /**
     * Returns the id attribute.
     *
     * @return string The id attribute.
     */
    protected function getIdAttribute(): string
    {
        return $this->sku_id;
    }
}
