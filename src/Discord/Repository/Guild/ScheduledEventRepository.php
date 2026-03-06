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

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function React\Promise\resolve;

/**
 * Contains scheduled events on a guild.
 *
 * @see ScheduledEvent
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method ScheduledEvent|null get(string $discrim, $key)
 * @method ScheduledEvent|null pull(string|int $key, $default = null)
 * @method ScheduledEvent|null first()
 * @method ScheduledEvent|null last()
 * @method ScheduledEvent|null find(callable $callback)
 */
class ScheduledEventRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'get' => Endpoint::GUILD_SCHEDULED_EVENT,
        'create' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'update' => Endpoint::GUILD_SCHEDULED_EVENT,
        'delete' => Endpoint::GUILD_SCHEDULED_EVENT,
    ];

    /**
     * @inheritDoc
     */
    protected $class = ScheduledEvent::class;

    /**
     * @inheritDoc
     *
     * @param bool $with_user_count Whether to include number of users subscribed to each event
     *
     * @return PromiseInterface<ScheduledEvent>
     */
    public function fetch(string $id, bool $fresh = false, bool $with_user_count = false): PromiseInterface
    {
        if (! $with_user_count) {
            return parent::fetch($id, $fresh);
        }

        if (! $fresh && $part = $this->get($this->discrim, $id)) {
            if (isset($part->user_count)) {
                return resolve($part);
            }
        }

        $part = $this->factory->part($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        $endpoint->addQuery('with_user_count', $with_user_count);

        return $this->http->get($endpoint)->then(function ($response) use ($part, $id) {
            $part->fill(array_merge($this->vars, (array) $response));
            $part->created = true;

            return $this->cache->set($id, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Get the counts for users subscribed to a scheduled event.
     *
     * @param string $id The scheduled event id.
     *
     * @return PromiseInterface<int> The count of users subscribed to the scheduled event.
     * 
     * @since 10.46.0
     */
    public function getUsersCount(string $id): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_SCHEDULED_EVENT_USERS_COUNT, $this->vars['guild_id'], $id));
    }

    /**
     * Get users for a specific scheduled event exception.
     *
     * Mirrors the query options of ScheduledEvent::getUsers.
     *
     * @param string $scheduledEventId
     * @param string $exceptionId
     * @param array  $options
     *
     * @return PromiseInterface<ExCollectionInterface<User>>
     * 
     * @since 10.46.0
     */
    public function getExceptionUsers(string $scheduledEventId, string $exceptionId, array $options = []): PromiseInterface
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
            return \React\Promise\reject(new \RangeException('Can only specify one of before after.'));
        }

        $endpoint = Endpoint::bind(Endpoint::GUILD_SCHEDULED_EVENT_EXCEPTION_USERS, $this->vars['guild_id'], $scheduledEventId, $exceptionId);
        $endpoint->addQuery('limit', $options['limit']);
        $endpoint->addQuery('with_member', $options['with_member']);

        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before'] instanceof User ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after'] instanceof User ? $options['after']->id : $options['after']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            /** @var ExCollectionInterface<User> $users */
            $users = new ($this->discord->getCollectionClass());

            $guild = $this->discord->guilds->get('id', $this->vars['guild_id']);

            foreach ($responses as $response) {
                if (isset($response->member) && ! $user = $guild->members->get('id', $response->user->id)) {
                    $user = $guild->members->create($response->member, true);
                    $guild->members->pushItem($user);
                } elseif (! $user = $this->discord->users->get('id', $response->user->id)) {
                    $user = $this->discord->users->create($response->user, true);
                    $this->discord->users->pushItem($user);
                }

                $users->pushItem($user);
            }

            return $users;
        });
    }
}
