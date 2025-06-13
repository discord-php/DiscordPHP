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

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents an Activity Instance.
 *
 * @link https://discord.com/developers/docs/resources/application#get-application-activity-instance-activity-instance-object
 *
 * @since 10.17.0
 *
 * @property string                       $application_id Application ID.
 * @property string                       $instance_id    Activity Instance ID.
 * @property string                       $launch_id      Unique identifier for the launch.
 * @property ActivityLocation             $location       Location the instance is running in.
 * @property ExCollectionInterface|User[] $users          IDs of the Users currently connected to the instance.
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

    /**
     * Returns a collection of users currently connected to the instance.
     *
     * @return ExCollectionInterface|User[]
     */
    protected function getUsersAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(User::class);

        foreach ($this->attributes['users'] as $user) {
            $collection->pushItem($this->discord->users->get('id', $user->id) ?: $this->factory->part(User::class, (array) $user, true));
        }

        return $collection;
    }
}
