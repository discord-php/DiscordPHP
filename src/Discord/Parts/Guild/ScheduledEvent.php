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

use function React\Promise\reject;

/**
 * A representation of a scheduled event in a guild.
 *
 * @link https://discord.com/developers/docs/resources/guild-scheduled-event
 *
 * @since 7.0.0
 *
 * @property      string       $id                   The id of the scheduled event.
 * @property      string       $guild_id             The guild id which the scheduled event belongs to.
 * @property-read Guild|null   $guild                The guild which the scheduled event belongs to.
 * @property      ?string|null $channel_id           The channel id in which the scheduled event will be hosted, or null if scheduled entity type is EXTERNAL.
 * @property-read Channel|null $channel              The channel in which the scheduled event will be hosted, or null.
 * @property      ?string|null $creator_id           The id of the user that created the scheduled event.
 * @property      string       $name                 The name of the scheduled event (1-100 characters).
 * @property      ?string|null $description          The description of the scheduled event (1-1000 characters).
 * @property      Carbon       $scheduled_start_time The time the scheduled event will start.
 * @property      Carbon|null  $scheduled_end_time   The time the scheduled event will end, required if entity_type is EXTERNAL.
 * @property      int          $privacy_level        The privacy level of the scheduled event.
 * @property      int          $status               The status of the scheduled event.
 * @property      int          $entity_type          The type of the scheduled event.
 * @property      ?string      $entity_id            The id of an entity associated with a guild scheduled event.
 * @property      ?object      $entity_metadata      Additional metadata for the guild scheduled event.
 * @property      User|null    $creator              The user that created the scheduled event.
 * @property      int|null     $user_count           The number of users subscribed to the scheduled event.
 * @property      ?string|null $image                The cover image URL of the scheduled event.
 * @property-read string|null  $image_hash           The cover image hash of the scheduled event.
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
    public const STATUS_CANCELED = 4;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'guild_id',
        'channel_id',
        'creator_id',
        'name',
        'description',
        'image',
        'scheduled_start_time',
        'scheduled_end_time',
        'privacy_level',
        'status',
        'entity_type',
        'entity_id',
        'entity_metadata',
        'creator',
        'user_count',
    ];

    /**
     * Get a list of guild scheduled event users subscribed to a guild scheduled
     * event.
     * Returns a list of guild scheduled event user objects on success.
     * Guild member data, if it exists, is included if the with_member query
     * parameter is set.
     *
     * @link https://discord.com/developers/docs/resources/guild-scheduled-event#get-guild-scheduled-event-users
     *
     * @throws \RangeException
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
        $resolver->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($options);
        if (isset($options['before'], $options['after'])) {
            return reject(new \RangeException('Can only specify one of before after.'));
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

            $guild = $this->guild;

            foreach ($responses as $response) {
                if (isset($response->member) && ! $user = $guild->members->get('id', $response->user->id)) {
                    $user = $guild->members->create((array) $response->member, true);
                    $guild->members->pushItem($user);
                } elseif (! $user = $this->discord->users->get('id', $response->user->id)) {
                    $user = $this->discord->users->create((array) $response->user, true);
                    $this->discord->users->pushItem($user);
                }

                $users->pushItem($user);
            }

            return $users;
        });
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild which the scheduled event belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->attributes['guild_id']);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|null The channel in which the scheduled event will be hosted, or null.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (! isset($this->attributes['channel_id'])) {
            return null;
        }

        if ($guild = $this->guild) {
            if ($channel = $guild->channels->get('id', $this->channel_id)) {
                return $channel;
            }
        }

        return $this->discord->getChannel($this->attributes['channel_id']);
    }

    /**
     * Returns the image attribute.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the guild scheduled event cover image if exists.
     */
    public function getImageAttribute(?string $format = null, int $size = 1024): ?string
    {
        if (! isset($this->attributes['image'])) {
            return null;
        }

        $allowed = ['png', 'jpg', 'webp'];

        if (! in_array(strtolower($format), $allowed)) {
            $format = 'png';
        }

        return "https://cdn.discordapp.com/guild-events/{$this->id}/{$this->attributes['image']}.{$format}?size={$size}";
    }

    /**
     * Returns the image hash.
     *
     * @return string|null The guild scheduled event cover image hash if exists.
     */
    protected function getImageHashAttribute(): ?string
    {
        return $this->attributes['image'] ?? null;
    }

    /**
     * Returns the created at attribute.
     *
     * @return Carbon The time the scheduled event will start.
     *
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
     *
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
    protected function getCreatorAttribute(): ?User
    {
        if (isset($this->attributes['creator_id']) && $user = $this->discord->users->get('id', $this->attributes['creator_id'])) {
            return $user;
        }

        if (isset($this->attributes['creator'])) {
            if ($user = $this->discord->users->get('id', $this->attributes['creator']->id)) {
                return $user;
            }

            return $this->factory->part(User::class, (array) $this->attributes['creator'], true);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild-scheduled-event#create-guild-scheduled-event-json-params
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
            'image' => $this->image_hash,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild-scheduled-event#modify-guild-scheduled-event-json-params
     */
    public function getUpdatableAttributes(): array
    {
        $attr = [
            'name' => $this->name,
            'privacy_level' => $this->privacy_level,
            'scheduled_start_time' => $this->attributes['scheduled_start_time'],
            'scheduled_end_time' => $this->attributes['scheduled_end_time'],
            'entity_type' => $this->entity_type,
            'status' => $this->status,
            'image' => $this->image_hash,
        ];

        if (array_key_exists('channel_id', $this->attributes)) {
            $attr['channel_id'] = $this->channel_id;
        }

        if (array_key_exists('entity_metadata', $this->attributes)) {
            $attr['entity_metadata'] = $this->entity_metadata;
        }

        if (array_key_exists('description', $this->attributes)) {
            $attr['description'] = $this->description;
        }

        return $attr;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_scheduled_event_id' => $this->id,
        ];
    }
}
