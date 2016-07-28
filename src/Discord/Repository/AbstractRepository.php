<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Discord\Factory\Factory;
use Discord\Helpers\Collection;
use Discord\Http\Http;
use Discord\Parts\Part;
use Discord\Wrapper\CacheWrapper;
use React\Promise\Deferred;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractRepository implements RepositoryInterface, ArrayAccess, Countable, IteratorAggregate
{
    /**
     * The discriminator.
     *
     * @var string Discriminator.
     */
    protected $discrim = 'id';

    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The Cache wrapper.
     *
     * @var CacheWrapper Cache.
     */
    protected $cache;

    /**
     * The parts factory.
     *
     * @var Factory Parts factory.
     */
    protected $factory;

    /**
     * The collection of items.
     *
     * @var Collection Items.
     */
    protected $collection;

    /**
     * Endpoints for interacting with the Discord servers.
     *
     * @var array Endpoints.
     */
    protected $endpoints = [];

    /**
     * The part that the repository serves.
     *
     * @var string The part that the repository serves.
     */
    protected $part;

    /**
     * Variables that are related to the repository.
     *
     * @var array Variables.
     */
    protected $vars = [];

    /**
     * AbstractRepository constructor.
     *
     * @param Http         $http    The HTTP client.
     * @param CacheWrapper $cache   The cache wrapper.
     * @param Factory      $factory The parts factory.
     * @param array        $vars    An array of variables used for the endpoint.
     */
    public function __construct(Http $http, CacheWrapper $cache, Factory $factory, $vars = [])
    {
        $this->http       = $http;
        $this->cache      = $cache;
        $this->factory    = $factory;
        $this->collection = new Collection([], $this->discrim);
        $this->vars       = $vars;
    }

    /**
     * Freshens the repository collection.
     *
     * @return \React\Promise\Promise
     */
    public function freshen()
    {
        if (! isset($this->endpoints['all'])) {
            return \React\Promise\reject(new \Exception('You cannot freshen this repository.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $this->replaceWithVariables(
                $this->endpoints['all']
            )
        )->then(function ($response) {
            $this->fill([]);

            foreach ($response as $value) {
                $value = array_merge($this->vars, (array) $value);
                $part = $this->factory->create($this->part, $value, true);

                $this->push($part);
            }
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $attributes = [])
    {
        $attributes = array_merge($attributes, $this->vars);

        return $this->factory->create($this->part, $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Part &$part)
    {
        if ($part->created) {
            $method     = 'patch';
            $endpoint   = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['update']));
            $attributes = $part->getUpdatableAttributes();

            if (! isset($this->endpoints['update'])) {
                return \React\Promise\reject(new \Exception('You cannot update this part.'));
            }
        } else {
            $method     = 'post';
            $endpoint   = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['create']));
            $attributes = $part->getCreatableAttributes();

            if (! isset($this->endpoints['create'])) {
                return \React\Promise\reject(new \Exception('You cannot create this part.'));
            }
        }

        $deferred = new Deferred();

        $this->http->{$method}(
            $endpoint,
            $attributes
        )->then(function ($response) use ($deferred, &$part, $method) {
            $part->fill((array) $response);

            $part->created = true;
            $part->deleted = false;

            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Part &$part)
    {
        if (! $part->created) {
            return \React\Promise\reject(new \Exception('You cannot delete a non-existant part.'));
        }

        if (! isset($this->endpoints['delete'])) {
            return \React\Promise\reject(new \Exception('You cannot delete this part.'));
        }

        $deferred = new Deferred();

        $this->http->delete(
            $part->replaceWithVariables(
                $this->replaceWithVariables(
                    $this->endpoints['delete']
                )
            )
        )->then(function ($response) use ($deferred, &$part) {
            $part->created = false;

            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function fresh(Part &$part)
    {
        if (! $part->created) {
            return \React\Promise\reject(new \Exception('You cannot get a non-existant part.'));
        }

        if (! isset($this->endpoints['get'])) {
            return \React\Promise\reject(new \Exception('You cannot get this part.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $part->replaceWithVariables(
                $this->replaceWithVariables(
                    $this->endpoints['get']
                )
            )
        )->then(function ($response) use ($deferred, &$part) {
            $part->fill($response);

            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        if ($part = $this->get('id', $id)) {
            return \React\Promise\resolve($part);
        }

        if (! isset($this->endpoints['get'])) {
            return \React\Promise\reject(new \Exception('You cannot get this part.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $this->replaceWithVariables(
                str_replace(':id', $id, $this->endpoints['get'])
            )
        )->then(function ($response) use ($deferred) {
            $part = $this->factory->create($this->part, $response, true);

            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Replaces variables in string with syntax :{varname}.
     *
     * @param string $string A string with placeholders.
     *
     * @return string A string with placeholders replaced.
     */
    protected function replaceWithVariables($string)
    {
        if (preg_match_all('/:([a-z_]+)/', $string, $matches)) {
            list(
                $original,
                $vars
            ) = $matches;

            foreach ($vars as $key => $var) {
                if (isset($this->vars[$var])) {
                    $string = str_replace($original[$key], $this->vars[$var], $string);
                }
            }
        }

        return $string;
    }

    /**
     * Returns how many items are in the repository.
     *
     * @return int Count.
     */
    public function count()
    {
        return $this->collection->count();
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->collection->getIterator();
    }

    /**
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->collection->offsetExists($key);
    }

    /**
     * Get an item at a given offset.
     *
     * @param mixed $key
     *
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->collection->offsetGet($key);
    }

    /**
     * Set the item at a given offset.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->collection->offsetSet($key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     *
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->collection->offsetUnset($key);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->collection->jsonSerialize();
    }

    /**
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of attributes.
     */
    public function __debugInfo()
    {
        return $this->all();
    }

    /**
     * Handles dynamic calls to the repository.
     *
     * @param string $function The function called.
     * @param array  $params   Array of parameters.
     *
     * @return mixed
     */
    public function __call($function, array $params)
    {
        return call_user_func_array([$this->collection, $function], $params);
    }
}
