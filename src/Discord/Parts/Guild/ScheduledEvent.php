<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A representation of a scheduled event in a guild.
 *
 * @property string       $id                   The id of the scheduled event.
 * @property Guild        $guild                The guild which the scheduled event belongs to.
 * @property string       $guild_id             The guild id which the scheduled event belongs to.
 * @property Channel|null $channel              The channel in which the scheduled event will be hosted, or null.
 * @property string|null  $channel_id           The channel id in which the scheduled event will be hosted, or null if scheduled entity type is EXTERNAL.
 * @property string|null  $creator_id           The id of the user that created the scheduled event.
 * @property string|null  $description          The description of the scheduled event (1-1000 characters).
 * @property Carbon       $scheduled_start_time The time the scheduled event will start.
 * @property Carbon|null  $scheduled_end_time   The time the scheduled event will end, required if entity_type is EXTERNAL.
 * @property int          $privacy_level        The privacy level of the scheduled event.
 * @property int          $status               The status of the scheduled event.
 * @property int          $entity_type          The type of the scheduled event.
 * @property string|null  $entity_id            The id of an entity associated with a guild scheduled event.
 * @property object|null  $entity_metadata      Additional metadata for the guild scheduled event.
 * @property User|null    $creator              The user that created the scheduled event.
 * @property int          $user_count           The number of users subscribed to the scheduled event.
 */
class ScheduledEvent extends Part
{
    public const PRIVACY_LEVEL_GUILD_ONLY = 2;

    public const ENTITY_TYPE_STAGE_INSTANCE = 1;
    public const ENTITY_TYPE_VOICE = 2;
    public const ENTITY_TYPE_EXTERNAL = 3;

    public const STATUS_SCHEDULED = 1;
    public const STATUS_ACTIVE = 2;
    public const STATUS_COMPLETED = 3;
    public const STATUS_CANCELED  = 4;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'guild_id',
        'channel_id',
        'creator_id',
        'name',
        'description',
        'scheduled_start_time',
        'scheduled_end_time',
        'privacy_level',
        'status',
        'entity_type',
        'entity_id',
        'entity_metadata',
        'creator',
        'user_count'
    ];

    /**
     * Get a list of guild scheduled event users subscribed to a guild scheduled event. Returns a list of guild scheduled event user objects on success. Guild member data, if it exists, is included if the with_member query parameter is set.
     *
     * @return ExtendedPromiseInterface
     */
    public function getUsers(array $options): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['limit' => 100, 'with_member' => false]);
        $resolver->setDefined(['before', 'after']);
        $resolver->setAllowedTypes('before', [User::class, 'string']);
        $resolver->setAllowedTypes('after', [User::class, 'string']);
        $resolver->setAllowedTypes('with_member', 'bool');
        $resolver->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after'])) {
            return \React\Promise\reject(new \Exception('Can only specify one of before after.'));
        }

        $endpoint = Endpoint::bind(Endpoint::GUILD_SCHEDULED_EVENT_USERS, $this->guild_id, $this->id);
        $endpoint->addQuery('limit', $options['limit']);
        $endpoint->addQuery('with_member', $options['with_member']);

        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before'] instanceof User ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after'] instanceof User ? $options['after']->id : $options['after']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            $users = new Collection();

            foreach ($responses as $response) {
                if (isset($response->member) && ! $user = $this->guild->members->get('id', $response->user->id)) {
                    $user = $this->factory->create(Member::class, $response->member, true);
                    $this->guild->members->push($user);
                } else if (! $user = $this->discord->users->get('id', $response->user->id)) {
                    $user = $this->factory->create(User::class, $response->user, true);
                    $this->discord->users->push($user);
                }

                $users->push($user);
            }

            return $users;
        });
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild      The guild which the scheduled event belongs to.
     * @throws \Exception
     */
    protected function getGuildAttribute(): Guild
    {
        if ($guild = $this->discord->guilds->get('id', $this->attributes['guild_id'])) {
            return $guild;
        }

        return $this->factory->create(Guild::class, $this->attributes['guild'], true);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel    The channel in which the scheduled event will be hosted, or null.
     * @throws \Exception
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (isset($this->attributes['channel_id']) && $channel = $this->discord->getChannel($this->attributes['channel_id'])) {
            return $channel;
        }

        return null;
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon     The time the scheduled event will start.
     * @throws \Exception
     */
    protected function getScheduledStartTimeAttribute(): Carbon
    {
        return new Carbon($this->attributes['scheduled_start_time']);
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon|null The time the scheduled event will end, required if entity_type is EXTERNAL.
     * @throws \Exception
     */
    protected function getScheduledEndTimeAttribute(): ?Carbon
    {
        if (! isset($this->attributes['scheduled_end_time'])) {
            return null;
        }

        return new Carbon($this->attributes['scheduled_end_time']);
    }

    /**
     * Gets the user that created the scheduled event.
     *
     * @return User|null The user that created the scheduled event.
     */
    protected function getCreatorAttribute(): ?Part
    {
        if (isset($this->attributes['creator_id']) && $user = $this->discord->users->get('id', $this->attributes['creator_id'])) {
            return $user;
        }

        if (isset($this->attributes['user'])) {
            if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
                return $user;
            }

            return $this->factory->part(User::class, $this->attributes['user'], true);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'channel_id' => $this->channel_id,
            'entity_metadata' => $this->entity_metadata,
            'name' => $this->name,
            'privacy_level' => $this->privacy_level,
            'scheduled_start_time' => $this->attributes['scheduled_start_time'],
            'scheduled_end_time' => $this->attributes['scheduled_end_time'],
            'description' => $this->description,
            'entity_type' => $this->entity_type,
            'status' => $this->status,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'channel_id' => $this->channel_id,
            'entity_metadata' => $this->entity_metadata,
            'name' => $this->name,
            'privacy_level' => $this->privacy_level,
            'scheduled_start_time' => $this->attributes['scheduled_start_time'],
            'scheduled_end_time' => $this->attributes['scheduled_end_time'],
            'description' => $this->description,
            'entity_type' => $this->entity_type,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_scheduled_event_id' => $this->id,
        ];
    }
}
