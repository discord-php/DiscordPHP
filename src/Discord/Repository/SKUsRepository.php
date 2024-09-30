<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\SKUs\SKU;
use React\Promise\ExtendedPromiseInterface;

use function React\Promise\resolve;

/**
 * Contains SKUs of an application.
 *
 * @see SKU
 * @see \Discord\Parts\User\Client
 *
 * @since 10.0.0
 *
 * @method SKU|null get(string $discrim, $key)
 * @method SKU|null pull(string|int $key, $default = null)
 * @method SKU|null first()
 * @method SKU|null last()
 * @method SKU|null find(callable $callback)
 */
class SKUsRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::APPLICATION_SKUS,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = SKU::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id;

        parent::__construct($discord, $vars);
    }

    /**
     * @param object $response
     *
     * @return ExtendedPromiseInterface<static>
     */
    protected function cacheFreshen($response): ExtendedPromiseInterface
    {
        foreach ($response as $value) foreach ($value as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        return $this->cache->setMultiple($items)->then(fn ($success) => $this);
    }
}
