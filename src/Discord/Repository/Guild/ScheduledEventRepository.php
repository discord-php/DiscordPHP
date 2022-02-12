<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains scheduled events to guilds.
 *
 * @see \Discord\Parts\Guild\ScheduledEvent
 * @see \Discord\Parts\Guild\Guild
 *
 * @method ScheduledEvent|null get(string $discrim, $key)  Gets an item from the collection.
 * @method ScheduledEvent|null first()                     Returns the first element of the collection.
 * @method ScheduledEvent|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method ScheduledEvent|null find(callable $callback)    Runs a filter callback over the repository.
 */
class ScheduledEventRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'get' => Endpoint::GUILD_SCHEDULED_EVENT,
        'create' => Endpoint::GUILD_SCHEDULED_EVENTS,
        'update' => Endpoint::GUILD_SCHEDULED_EVENT,
        'delete' => Endpoint::GUILD_SCHEDULED_EVENT,
    ];

    /**
     * @inheritdoc
     */
    protected $class = ScheduledEvent::class;

    /**
     * @inheritdoc
     *
     * @param bool $with_user_count Whether to include number of users subscribed to each event
     */
    public function fetch(string $id, bool $fresh = false, bool $with_user_count = false): ExtendedPromiseInterface
    {
        if (! $with_user_count) {
            return parent::fetch($id, $fresh);
        }

        if (! $fresh && $part = $this->get($this->discrim, $id)) {
            if (isset($part->user_count)) {
                return \React\Promise\resolve($part);
            }
        }

        $part = $this->factory->create($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        $endpoint->addQuery('with_user_count', $with_user_count);

        return $this->http->get($endpoint)->then(function ($response) {
            $part = $this->factory->create($this->class, array_merge($this->vars, (array) $response), true);
            $this->push($part);

            return $part;
        });
    }
}
