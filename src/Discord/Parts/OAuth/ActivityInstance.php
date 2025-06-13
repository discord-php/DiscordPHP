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

namespace Discord\Parts\OAuth;

use Discord\Parts\Part;

/**
 * Represents an Activity Instance.
 *
 * @property string           $application_id Application ID.
 * @property string           $instance_id    Activity Instance ID.
 * @property string           $launch_id      Unique identifier for the launch.
 * @property ActivityLocation $location       Location the instance is running in.
 * @property array            $users          IDs of the Users currently connected to the instance.
 */
class ActivityInstance extends Part
{
    protected $fillable = [
        'application_id',
        'instance_id',
        'launch_id',
        'location',
        'users',
    ];

    /**
     * Gets the location object.
     *
     * @return ActivityLocation|null
     */
    protected function getLocationAttribute(): ?ActivityLocation
    {
        if (!isset($this->attributes['location'])) {
            return null;
        }

        return $this->factory->part(ActivityLocation::class, (array) $this->attributes['location'], true);
    }
}
