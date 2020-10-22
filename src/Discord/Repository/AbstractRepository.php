<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
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
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\reject as Reject;
use function React\Promise\resolve as Resolve;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @author Aaron Scherer <aequasi@gmail.com>, David Cole <david.cole1340@gmail.com>
 */
abstract class AbstractRepository extends Collection
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
     * The parts factory.
     *
     * @var Factory Parts factory.
     */
    protected $factory;

    /**
     * Endpoints for interacting with the Discord servers.
     *
     * @var array Endpoints.
     */
    protected $endpoints = [];

    /**
     * Variables that are related to the repository.
     *
     * @var array Variables.
     */
    protected $vars = [];

    /**
     * AbstractRepository constructor.
     *
     * @param Http    $http    The HTTP client.
     * @param Factory $factory The parts factory.
     * @param array   $vars    An array of variables used for the endpoint.
     */
    public function __construct(Http $http, Factory $factory, array $vars = [])
    {
        $this->http = $http;
        $this->factory = $factory;
        $this->vars = $vars;

        parent::__construct();
    }

    /**
     * Freshens the repository collection.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function freshen(): PromiseInterface
    {
        if (! isset($this->endpoints['all'])) {
            return Reject(new \Exception('You cannot freshen this repository.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $this->replaceWithVariables(
                $this->endpoints['all']
            ),
            null,
            [],
            false
        )->then(function ($response) use ($deferred) {
            $this->fill([]);

            foreach ($response as $value) {
                $value = array_merge($this->vars, (array) $value);
                $part = $this->factory->create($this->class, $value, true);

                $this->push($part);
            }

            $deferred->resolve($this);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Builds a new, empty part.
     *
     * @param array $attributes The attributes for the new part.
     *
     * @return Part       The new part.
     * @throws \Exception
     */
    public function create(array $attributes = []): Part
    {
        $attributes = array_merge($attributes, $this->vars);

        return $this->factory->create($this->class, $attributes);
    }

    /**
     * Attempts to save a part to the Discord servers.
     *
     * @param Part $part The part to save.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function save(Part $part): PromiseInterface
    {
        if ($part->created) {
            $method = 'patch';
            $endpoint = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['update']));
            $attributes = $part->getUpdatableAttributes();

            if (! isset($this->endpoints['update'])) {
                return Reject(new \Exception('You cannot update this part.'));
            }
        } else {
            $method = 'post';
            $endpoint = $part->replaceWithVariables($this->replaceWithVariables(@$this->endpoints['create']));
            $attributes = $part->getCreatableAttributes();

            if (! isset($this->endpoints['create'])) {
                return Reject(new \Exception('You cannot create this part.'));
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

            $this->push($part);
            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Attempts to delete a part on the Discord servers.
     *
     * @param Part|snowflake $part The part to delete.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function delete($part): PromiseInterface
    {
        if (! ($part instanceof Part)) {
            $part = $this->factory->part($this->class, ['id' => $part], true);
        }

        if (! $part->created) {
            return Reject(new \Exception('You cannot delete a non-existant part.'));
        }

        if (! isset($this->endpoints['delete'])) {
            return Reject(new \Exception('You cannot delete this part.'));
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
     * Returns a part with fresh values.
     *
     * @param Part $part The part to get fresh values.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function fresh(Part $part): PromiseInterface
    {
        if (! $part->created) {
            return Reject(new \Exception('You cannot get a non-existant part.'));
        }

        if (! isset($this->endpoints['get'])) {
            return Reject(new \Exception('You cannot get this part.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $part->replaceWithVariables(
                $this->replaceWithVariables(
                    $this->endpoints['get']
                )
            )
        )->then(function ($response) use ($deferred, &$part) {
            $part->fill((array) $response);

            $deferred->resolve($part);
        }, function ($e) use ($deferred) {
            $deferred->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Force gets a part from the Discord servers.
     *
     * @param string $id The ID to search for.
     *
     * @return PromiseInterface
     * @throws \Exception
     */
    public function fetch(string $id): PromiseInterface
    {
        if ($part = $this->get('id', $id)) {
            return Resolve($part);
        }

        if (! isset($this->endpoints['get'])) {
            return Reject(new \Exception('You cannot get this part.'));
        }

        $deferred = new Deferred();

        $this->http->get(
            $this->replaceWithVariables(
                str_replace(':id', $id, $this->endpoints['get'])
            )
        )->then(function ($response) use ($deferred) {
            $part = $this->factory->create($this->class, (array) $response, true);

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
    protected function replaceWithVariables(string $string): string
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
     * Handles debug calls from var_dump and similar functions.
     *
     * @return array An array of attributes.
     */
    public function __debugInfo(): array
    {
        return $this->jsonSerialize();
    }
}
