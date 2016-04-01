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

use Discord\Factory\PartFactory;
use Discord\Guzzle;
use Discord\Http\Http;
use Discord\Parts\Part;
use Discord\Repository\RepositoryInterface;
use Discord\Wrapper\CacheWrapper;
use Illuminate\Support\Collection;
use React\Promise\Deferred;

/**
 * @author Aaron Scherer <aequasi@gmail.com>
 */
abstract class AbstractRepository extends Collection implements RepositoryInterface
{
    /**
     * @var Http
     */
    protected $http;

    /**
     * @var CacheWrapper
     */
    protected $cache;

    /**
     * @var PartFactory
     */
    protected $partFactory;

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
     * @param Http         $http
     * @param CacheWrapper $cache
     * @param PartFactory  $partFactory
     * @param array        $vars 
     */
    public function __construct(Http $http, CacheWrapper $cache, PartFactory $partFactory, $vars = [])
    {
        $this->http        = $http;
        $this->cache       = $cache;
        $this->partFactory = $partFactory;
        $this->vars        = $vars;
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
            $this->items = [];

            foreach ($response as $value) {
                $value = array_merge($this->vars, (array) $value);
                $part = $this->partFactory->create($this->part, $value, true);

                $this->push($part);
            }
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });
    }

    /**
     * Get an item from the collection with a key and value.
     *
     * @param mixed $key The key to match with the value.
     * @param mixed $value The value to match with the key.
     *
     * @return mixed The value or null.
     */
    public function get($key, $value = null)
    {
        foreach ($this->items as $item) {
            if ($item->{$key} == $value) {
                return $value;
            }
        }
    }

    /**
     * Gets a collection of items from the repository with a key and value.
     *
     * @param mixed $key The key to match with the value.
     * @param mixed $value The value to match with the key.
     *
     * @return Collection A collection.
     */
    public function getAll($key, $value = null)
    {
        $collection = new Collection();

        foreach ($this->items as $item) {
            if ($item->{$key} == $value) {
                $collection->push($value);
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function save(Part &$part)
    {
        if ($part->created) {
            $method = 'patch';
            $endpoint = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['update']));
            $attributes = $part->getCreatableAttributes();

            if (! isset($this->endpoints['update'])) {
                return \React\Promise\reject(new \Exception('You cannot update this part.'));
            }
        } else {
            $method = 'post';
            $endpoint = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['create']));
            $attributes = $part->getUpdatableAttributes();

            if (! isset($this->endpoints['create'])) {
                return \React\Promise\reject(new \Exception('You cannot create this part.'));
            }
        }

        $deferred = new Deferred();

        $this->http->{$method}(
            $endpoint,
            $attributes
        )->then(function ($response) use ($deferred, &$part, $method) {
            $part->fill($response);

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
}
