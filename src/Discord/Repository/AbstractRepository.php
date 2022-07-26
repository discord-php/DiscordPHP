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

use Discord\Factory\Factory;
use Discord\Helpers\CacheWrapper;
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
 * @property-read \WeakReference[] $items          Repository cache items containing cache key => weak references to the cache.
 * @property-read CacheWrapper     $cache          The react/cache wrapper.
 * @property-read string           $cacheKeyPrefix Cache key prefix.
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
     * @var CacheWrapper
     */
    protected $cache;

    /**
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
        $this->cacheKeyPrefix = substr(strrchr($this->class, '\\'), 1) . '.';
        $this->cache = new CacheWrapper($cacheInterface, $this->items);

        parent::__construct([], $this->discrim, $this->class);
    }

    /**
     * Freshens the repository cache.
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

            return $this->cache->set($this->cacheKeyPrefix.$part->{$this->discrim}, $part);
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

            return $this->cache->delete($this->cacheKeyPrefix.$part->{$this->discrim})->then(function () use ($part) {
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

            return $this->cache->set($this->cacheKeyPrefix.$part->{$this->discrim}, $part);
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
            $cacheKey = $this->cacheKeyPrefix.$id;
            $part = null;
            if (isset($this->items[$cacheKey])) {
                $part = $this->items[$cacheKey]->get();
            }

            return $this->cache->get($cacheKey, $part);
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $part = $this->factory->create($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        return $this->http->get($endpoint)->then(function ($response) {
            $part = $this->factory->create($this->class, array_merge($this->vars, (array) $response), true);

            return $this->cache->set($this->cacheKeyPrefix.$part->{$this->discrim}, $part);
        });
    }

    /**
     * @internal
     */
    protected function freshenCache($response): PromiseInterface
    {
        return $this->cache->deleteMultiple(array_keys($this->items))->then(function ($success) use ($response) {
            $parts = [];

            foreach ($response as $value) {
                $value = array_merge($this->vars, (array) $value);
                $part = $this->factory->create($this->class, $value, true);

                $cacheKey = $this->cacheKeyPrefix.$part->{$this->discrim};
                $parts[$cacheKey] = $part;
            }

            return $this->cache->setMultiple($parts)->then(function ($success) {
                return $this;
            });
        });
    }

    /**
     * @deprecated 7.1.4 Use async `$repository->cache->get()` or `$repository->fetch()`
     * @uses \React\Async\await() This method is blocking.
     * {@inheritdoc}
     */
    public function get(string $discrim, $key)
    {
        if ($discrim == $this->discrim && $this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        foreach ($this->items as $item) {
            if ($part = $item->get()) {
                if ($part->{$discrim} == $key) {
                    return $part;
                }
            }
        }

        return null;
    }

    /**
     * @deprecated 7.1.4 Use async `$repository->cache->get()` and `$repository->cache->delete()`
     * @uses \React\Async\await() This method is blocking.
     * {@inheritdoc}
     */
    public function pull($key, $default = null)
    {
        if ($item = $this->offsetGet($key)) {
            $default = $item;
            $this->offsetUnset($key);
        }

        return $default;
    }

    /**
     * @deprecated 7.1.4 Use async `$repository->cache->set()`
     * @uses \React\Async\await() This method is blocking.
     * {@inheritdoc}
     */
    public function pushItem($item): self
    {
        if (! is_null($this->class) && ! ($item instanceof $this->class)) {
            return $this;
        }

        if (is_object($item)) {
            $this->offsetSet($this->cacheKeyPrefix.$item->{$this->discrim}, $item);
        }

        return $this;
    }

    /**
     * Returns the first element of the cache which is not yet garbage collected.
     *
     * @return mixed
     */
    public function first()
    {
        /** @var WeakReference|null */
        if ($item = parent::first()) {
            return $item->get();
        }

        return null;
    }

    /**
     * Returns the last element of the cache which is not yet garbage collected.
     *
     * @return mixed
     */
    public function last()
    {
        /** @var WeakReference|null */
        if ($item = parent::last()) {
            return $item->get();
        }

        return null;
    }

    /**
     * Checks if the cache has an object.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @param array ...$keys
     */
    public function has(...$keys): bool
    {
        foreach ($keys as $key) {
            if (! $this->offsetExists($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(callable $callback): Collection
    {
        $collection = new Collection([], $this->discrim, $this->class);

        foreach ($this->items as $item) {
            if ($part = $item->get()) {
                if ($callback($part)) {
                    $collection->push($part);
                }
            }
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function find(callable $callback)
    {
        foreach ($this->items as $item) {
            if ($part = $item->get()) {
                if ($callback($part)) {
                    return $part;
                }
            }
        }

        return null;
    }

    /**
     * Clears the cache.
     * @uses \React\Async\await() This method is blocking.
     */
    public function clear(): void
    {
        if ($this->items) {
            $this->interface->cache->deleteMultiple(array_keys($this->items));
            parent::clear();
        }
    }

    /**
     * {@inheritdoc}
     * @todo test
     */
    public function map(callable $callback): Collection
    {
        $keys = array_keys($this->items);
        $values = array_map($callback, array_values($this->toArray()));

        foreach ($values as $key => $value) {
            $value[$key] = WeakReference::create($value);
        }

        return new Collection(array_combine($keys, $values), $this->discrim, $this->class);
    }

    /**
     * {@inheritdoc}
     * @todo test
     */
    public function merge(Collection $collection): Collection
    {
        $items2 = [];

        foreach ($collection->toArray() as $key => $value) {
            $items2[$this->cacheKeyPrefix.$key] = WeakReference::create($value);
        }

        $this->items = array_merge($this->items, $items2);

        return $this;
    }

    /**
     * Converts the weak caches to an array.
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
     * If the weak cache has an offset.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        $cacheKey = $this->cacheKeyPrefix.$offset;

        if (isset($this->items[$cacheKey])) {
            return true;
        }

        return await($this->cache->has($cacheKey));
    }

    /**
     * Gets an item from the cache.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $cacheKey = $this->cacheKeyPrefix.$offset;

        if ($item = $this->items[$cacheKey]) {
            return $item->get();
        }

        return await($this->cache->get($cacheKey));
    }

    /**
     * Sets an item into the cache.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $cacheKey = $this->cacheKeyPrefix.$offset;

        if (isset($this->items[$cacheKey])) {
            $this->cache->interface->set($cacheKey, $value);
            $this->items[$cacheKey] = WeakReference::create($value);

            return;
        }

        await($this->cache->set($cacheKey, $value));
    }

    /**
     * Unsets an index from the cache.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @param mixed offset
     */
    public function offsetUnset($offset): void
    {
        $cacheKey = $this->cacheKeyPrefix.$offset;

        if (isset($this->items[$cacheKey])) {
            $this->cache->interface->delete($cacheKey);
            unset($this->items[$cacheKey]);

            return;
        }

        await($this->cache->delete($cacheKey));
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns an iterator for the cache.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return (function () {
            foreach ($this->items as $key => $item) {
                if (($part = $item->get()) || ($part = await($this->cache->get($key)))) {
                    yield $part;
                }
            }
        })();
    }

    /**
     * @return CacheWrapper `cache`
     * @return string       `cacheKeyPrefix`
     * @return mixed
     */
    public function __get(string $key)
    {
        if (in_array($key, ['cache', 'cacheKeyPrefix'])) {
            return $this->{$key};
        }
    }
}
