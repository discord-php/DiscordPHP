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
 * Represents the collectibles a user has.
 *
 * @link https://discord.com/developers/docs/resources/user#collectibles
 *
 * @property ?Nameplate|null $nameplate The nameplate collectible object, if present.
 */
class Collectibles extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'nameplate',
    ];

    /**
     * Gets the nameplate collectible as a Nameplate object, if present.
     *
     * @return Nameplate|null
     */
    protected function getNameplateAttribute(): ?Nameplate
    {
        if (!isset($this->attributes['nameplate'])) {
            return null;
        }
        return $this->factory->part(Nameplate::class, (array) $this->attributes['nameplate'], true);
    }
}
