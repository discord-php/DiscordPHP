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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\ActivityInstanceRepository;
use React\Promise\PromiseInterface;

/**
 * Represents an Activity Instance.
 *
 * @link https://discord.com/developers/docs/resources/application#get-application-activity-instance-activity-instance-object
 *
 * @since 10.17.0
 *
 * @property string                             $application_id Application ID.
 * @property string                             $instance_id    Activity Instance ID.
 * @property string                             $launch_id      Unique identifier for the launch.
 * @property ActivityLocation                   $location       Location the instance is running in.
 * @property ExCollectionInterface<User>|User[] $users          IDs of the Users currently connected to the instance.
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
        return $this->attributePartHelper('location', ActivityLocation::class);
    }

    /**
     * Returns a collection of users currently connected to the instance.
     *
     * @return ExCollectionInterface<User>|User[]
     */
    protected function getUsersAttribute(): ExCollectionInterface
    {
        if (isset($this->attributes['users']) && $this->attributes['users'] instanceof ExCollectionInterface) {
            return $this->attributes['users'];
        }

        /** @var ExCollectionInterface<User> $collection */
        $collection = $this->discord->collection::for(User::class);

        foreach ($this->attributes['users'] as $user) {
            $collection->pushItem($this->discord->users->get('id', $user->id) ?? $this->factory->part(User::class, (array) $user, true));
        }

        $this->attributes['users'] = $collection;

        return $collection;
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return ActivityInstanceRepository The repository.
     */
    public function getRepository(): ActivityInstanceRepository
    {
        return $this->discord->application->activity_instances;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        return $this->discord->application->activity_instances->save($this, $reason);
    }
}
