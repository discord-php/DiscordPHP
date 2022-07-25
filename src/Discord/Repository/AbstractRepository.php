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

use ArrayIterator;
use Discord\Factory\Factory;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Part;
use React\Cache\CacheInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromiseInterface;
use Traversable;
use WeakReference;

use function React\Async\await;
use function React\Promise\reject;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author David Cole <david.cole1340@gmail.com>
 * 
 * @property-read WeakReference[] $items 
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
     * The react/cache Interface.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $cacheKeyPrefix;

    /**
     * AbstractRepository constructor.
     *
     * @param Http    $http    The HTTP client.
     * @param Factory $factory The parts factory.
     * @param array   $vars    An array of variables used for the endpoint.
     */
    public function __construct(Http $http, Factory $factory, array $vars = [], CacheInterface $cacheInterface)
    {
        $this->http = $http;
        $this->factory = $factory;
        $this->vars = $vars;
        $this->cache = $cacheInterface;
        $this->cacheKeyPrefix = substr(strrchr($this->class, '\\'), 1);

        parent::__construct([], $this->discrim, $this->class);
    }

    /**
     * Freshens the repository collection.
     *
     * @param array $queryparams Query string params to add to the request (no validation)
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function freshen(array $queryparams = []): ExtendedPromiseInterface
    {
        if (! isset($this->endpoints['all'])) {
            return reject(new \Exception('You cannot freshen this repository.'));
        }

        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        foreach ($queryparams as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            $this->clear();

            return $this->freshenCache($response);
        });
    }

    /**
     * Builds a new, empty part.
     *
     * @param array $attributes The attributes for the new part.
     * @param bool  $created
     *
     * @return Part       The new part.
     * @throws \Exception
     */
    public function create(array $attributes = [], bool $created = false): Part
    {
        $attributes = array_merge($attributes, $this->vars);

        return $this->factory->create($this->class, $attributes, $created);
    }

    /**
     * Attempts to save a part to the Discord servers.
     *
     * @param Part        $part   The part to save.
     * @param string|null $reason Reason for Audit Log (if supported).
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function save(Part $part, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($part->created) {
            if (! isset($this->endpoints['update'])) {
                return reject(new \Exception('You cannot update this part.'));
            }

            $method = 'patch';
            $endpoint = new Endpoint($this->endpoints['update']);
            $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));
            $attributes = $part->getUpdatableAttributes();
        } else {
            if (! isset($this->endpoints['create'])) {
                return reject(new \Exception('You cannot create this part.'));
            }

            $method = 'post';
            $endpoint = new Endpoint($this->endpoints['create']);
            $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));
            $attributes = $part->getCreatableAttributes();
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->{$method}($endpoint, $attributes, $headers)->then(function ($response) use (&$part) {
            $part->fill((array) $response);
            $part->created = true;
            $part->deleted = false;
            $cacheKey = $this->cacheKeyPrefix.'.'.$part->{$this->discrim};

            return $this->setCache($cacheKey, $part);
        });
    }

    /**
     * Attempts to delete a part on the Discord servers.
     *
     * @param Part|string $part   The part to delete.
     * @param string|null $reason Reason for Audit Log (if supported).
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function delete($part, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! ($part instanceof Part)) {
            $part = $this->factory->part($this->class, [$this->discrim => $part], true);
        }

        if (! $part->created) {
            return reject(new \Exception('You cannot delete a non-existant part.'));
        }

        if (! isset($this->endpoints['delete'])) {
            return reject(new \Exception('You cannot delete this part.'));
        }

        $endpoint = new Endpoint($this->endpoints['delete']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->delete($endpoint, null, $headers)->then(function ($response) use (&$part) {
            if ($response) {
                $part->fill((array) $response);
            }

            $part->created = false;
            $cacheKey = $this->cacheKeyPrefix.'.'.$part->{$this->discrim};

            return $this->deleteCache($cacheKey)->then(function () use ($part) {
                return $part;
            });
        });
    }

    /**
     * Returns a part with fresh values.
     *
     * @param Part  $part        The part to get fresh values.
     * @param array $queryparams Query string params to add to the request (no validation)
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function fresh(Part $part, array $queryparams = []): ExtendedPromiseInterface
    {
        if (! $part->created) {
            return reject(new \Exception('You cannot get a non-existant part.'));
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        foreach ($queryparams as $query => $param) {
            $endpoint->addQuery($query, $param);
        }

        return $this->http->get($endpoint)->then(function ($response) use (&$part) {
            $part->fill((array) $response);
            $cacheKey = $this->cacheKeyPrefix.'.'.$part->{$this->discrim};

            return $this->setCache($cacheKey, $part);
        });
    }

    /**
     * Gets a part from the repository or Discord servers.
     *
     * @param string $id    The ID to search for.
     * @param bool   $fresh Whether we should skip checking the cache.
     *
     * @return ExtendedPromiseInterface
     * @throws \Exception
     */
    public function fetch(string $id, bool $fresh = false): ExtendedPromiseInterface
    {
        if (! $fresh) {
            $cacheKey = $this->cacheKeyPrefix.'.'.$id;
            $part = null;
            if (isset($this->items[$cacheKey])) {
                $part = $this->items[$cacheKey]->get();
            }

            return $this->cache->get($this->cacheKeyPrefix.'.'.$id, $part);
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $part = $this->factory->create($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        return $this->http->get($endpoint)->then(function ($response) {
            $part = $this->factory->create($this->class, array_merge($this->vars, (array) $response), true);
            $cacheKey = $this->cacheKeyPrefix.'.'.$part->{$this->discrim};

            return $this->setCache($cacheKey, $part);
        });
    }

    /**
     * @internal
     */
    protected function freshenCache($response): PromiseInterface
    {
        return $this->cache->deleteMultiple(array_keys($this->items))->then(function ($success) use ($response) {
            if ($success) {
                $this->items = [];
            }

            $parts = $items = [];

            foreach ($response as $value) {
                $value = array_merge($this->vars, (array) $value);
                $part = $this->factory->create($this->class, $value, true);

                $cacheKey = $this->cacheKeyPrefix.'.'.$part->{$this->discrim};
                $parts[$cacheKey] = $part;
                $items[$cacheKey] = WeakReference::create($part);
            }

            return $this->cache->setMultiple($parts)->then(function ($success) use ($items) {
                if ($success) {
                    $this->items = $items;
                }

                return $this;
            });
        });
    }

    /**
     * @internal
     */
    protected function setCache($cacheKey, $part): PromiseInterface
    {
        return $this->cache->set($cacheKey, $part)->then(function ($success) use ($part, $cacheKey) {
            if ($success) {
                $this->items[$cacheKey] = WeakReference::create($part);
            }

            return $part;
        });
    }

    /**
     * @internal
     */
    protected function deleteCache($cacheKey): PromiseInterface
    {
        return $this->cache->delete($cacheKey)->then(function ($success) use ($cacheKey) {
            if ($success) {
                unset($this->items[$cacheKey]);
            }

            return $success;
        });
    }

    /**
     * Gets the cache interface.
     *
     * @return CacheInterface
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Returns the first element of the cache.
     *
     * @return mixed
     */
    public function first()
    {
        if ($item = parent::first()) {
            return $item->get();
        }

        return null;
    }

    /**
     * Returns the last element of the cache.
     *
     * @return mixed
     */
    public function last()
    {
        if ($item = parent::last()) {
            return $item->get();
        }

        return null;
    }

    /**
     * Checks if the array has an object.
     *
     * @param array ...$keys
     *
     * @deprecated 7.1.4
     *
     * @return bool
     */
    public function has(...$keys): bool
    {
        foreach ($keys as $key) {
            if (! await($this->cache->has($this->cacheKeyPrefix.'.'.$key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Runs a filter callback over the collection and
     * returns the first item where the callback returns
     * `true` when given the item.
     *
     * Returns `null` if no items returns `true` when called in
     * the callback.
     *
     * @param  callable $callback
     * @return mixed
     */
    public function find(callable $callback)
    {
        foreach ($this->toArray() as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Clears the collection.
     */
    public function clear(): void
    {
        await($this->cache->deleteMultiple(array_keys($this->items))->then(function ($success) {
            if ($success) {
                parent::clear();
            }
        }));
    }

    /**
     * Converts the cache to an array.
     *
     * @return array
     */
    public function toArray()
    {
        $items = [];

        foreach ($this->items as $key => $value) {
            $key = substr(strrchr($key, '.'), 1);
            $items[$key] = $value->get();
        }

        return $items;
    }

    /**
     * If the cache has an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->items[$this->cacheKeyPrefix.'.'.$offset]);
    }

    /**
     * Gets an item from the cache.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $cacheKey = $this->cacheKeyPrefix.'.'.$offset;

        return await($this->cache->get($cacheKey, parent::offsetGet($offset))->then(function ($part) use ($cacheKey) {
            if ($part !== null) {
                $this->items[$cacheKey] = WeakReference::create($part);
            }

            return $part;
        }));
    }

    /**
     * Sets an item into the cache.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        await($this->setCache($this->cacheKeyPrefix.'.'.$offset, $value));
    }

    /**
     * Unsets an index from the collection.
     *
     * @param mixed offset
     */
    public function offsetUnset($offset): void
    {
        await($this->deleteCache($this->cacheKeyPrefix.'.'.$offset));
    }

    /**
     * Returns the string representation of the collection.
     *
     * @return string
     */
    public function serialize(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Returns the string representation of the cache.
     *
     * @return string
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Unserializes the cache.
     *
     * @param string $serialized
     */
    public function unserialize(string $serialized): void
    {
        $this->__unserialize(json_decode($serialized));
    }

    /**
     * Unserializes the cache.
     *
     * @param array $serialized
     */
    public function __unserialize(array $serialized): void
    {
        $this->items = [];

        foreach ($serialized as $key => $value) {
            $key = $this->cacheKeyPrefix.'.'.$key;
            $this->items[$key] = WeakReference::create($value);
        }
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns an iterator for the collection.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        // TODO: yield from cache
        return new ArrayIterator($this->toArray());
    }
}
