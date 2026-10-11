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

namespace Discord\Parts\Application\Identity;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Game stats data; reading typed views leaves raw payload storage unchanged.
 *
 * @since 10.59.0
 * @link https://docs.discord.com/developers/resources/application-identity-profile#profile-data-object
 *
 * @property PrimaryProfileData|null                  $primary Pre-configured stats.
 * @property ExCollectionInterface<DynamicField>|null $dynamic Custom fields in payload order.
 */
class ProfileData extends Part
{
    protected $fillable = ['primary', 'dynamic'];

    /** @return PrimaryProfileData|null */
    protected function getPrimaryAttribute(): ?PrimaryProfileData
    {
        if (($this->attributes['primary'] ?? null) === null) {
            return null;
        }

        return $this->attributes['primary'] instanceof PrimaryProfileData
            ? $this->attributes['primary']
            : $this->createOf(PrimaryProfileData::class, $this->attributes['primary']);
    }

    /** @return ExCollectionInterface<DynamicField>|null */
    protected function getDynamicAttribute(): ?ExCollectionInterface
    {
        if (($this->attributes['dynamic'] ?? null) === null) {
            return null;
        }

        $collection = $this->discord->getCollectionClass()::for(DynamicField::class, null);
        foreach ($this->attributes['dynamic'] as $field) {
            if (! $field instanceof DynamicField) {
                $attributes = (array) $field;
                $field = $this->createOf(DynamicField::TYPES[$attributes['type'] ?? 0] ?? DynamicField::class, $attributes);
            }
            $collection->pushItem($field);
        }

        return $collection;
    }

    /** Serialize only supplied fields, without introducing null optional stats. */
    public function jsonSerialize(): array
    {
        return $this->getRawAttributes();
    }
}
