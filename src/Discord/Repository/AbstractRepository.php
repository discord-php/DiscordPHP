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
use Discord\Factory\Factory;
use Discord\Helpers\CacheWrapper;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Http\Http;
use Discord\Parts\Part;
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
 * @property-read CacheWrapper $cache The react/cache wrapper.
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
     * AbstractRepository constructor.
     *
     * @param Http    $http    The HTTP client.
     * @param Factory $factory The parts factory.
     * @param array   $vars    An array of variables used for the endpoint.
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $this->http = $discord->getHttpClient();
        $this->factory = $discord->getFactory();
        $this->vars = $vars;
        $this->cache = new CacheWrapper($discord, $discord->getCache(), $this->items, $this->class, $this->vars);

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

            return $this->cache->set($part->{$this->discrim}, $part)->then(function ($success) use ($part) {
                return $part;
            });
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

            return $this->cache->delete($part->{$this->discrim})->then(function () use ($part) {
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

            return $this->cache->set($part->{$this->discrim}, $part)->then(function ($success) use ($part) {
                return $part;
            });
        });
    }

    /**
     * Gets a part from the repository or Discord servers.
     *
     * @param string $id    The ID to search for.
     * @param bool   $fresh Whether we should skip checking the cache.
     *
     * @throws \Exception
     *
     * @return ExtendedPromiseInterface<Part>
     */
    public function fetch(string $id, bool $fresh = false): ExtendedPromiseInterface
    {
        if (! $fresh) {
            if (isset($this->items[$id]) && $part = $this->items[$id]->get()) {
                return $part;
            }

            return $this->cache->get($id);
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $part = $this->factory->part($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        return $this->http->get($endpoint)->then(function ($response) use ($part) {
            $part->fill(array_merge($this->vars, (array) $response));
            $part->created = true;

            return $this->cache->set($part->{$this->discrim}, $part)->then(function ($success) use ($part) {
                return $part;
            });
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

                $parts[$part->{$this->discrim}] = $part;
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
        if ($discrim == $this->discrim) {
            if (isset($this->items[$key]) && $item = $this->items[$key]->get()) {
                return $item;
            }

            return await($this->cache->get($key));
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
     * Pushes items to the collection.
     *
     * @param mixed ...$items
     *
     * @return self|Collection
     */
    public function push(...$items): self
    {
        if ($this->class === null) {
            return parent::push($items);
        }

        foreach ($items as $item) {
            if (is_a($item, $this->class)) {
                $key = $item->{$this->discrim};
                $values[$this->cache->key_prefix.$key] = $item->serialize();
                $this->items[$key] = WeakReference::create($item);
            }
        }

        $this->cache->interface->setMultiple($values);

        return $this;
    }

    /**
     * Pushes a single item to the repository.
     *
     * @deprecated 7.1.4 Use async `$repository->cache->set()`
     * @uses \React\Async\await() This method is blocking.
     *
     * @param Part $item
     *
     * @return self|Collection
     */
    public function pushItem($item): self
    {
        if ($this->class === null) {
            return parent::pushItem($item);
        }

        if (is_a($item, $this->class)) {
            $key = $item->{$this->discrim};
            $this->cache->interface->set($this->cache->key_prefix.$key, $item->serialize());
            $this->items[$key] = WeakReference::create($item);
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
            if (isset($item) && $part = $item->get()) {
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
            $this->interface->cache->deleteMultiple(array_map(function ($key) {
                return $this->cache->key_prefix.$key;
            }, array_keys($this->items)));

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
            $items2[$key] = WeakReference::create($value);
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

        foreach ($this->items as $value) {
            $items[] = $value->get();
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
        if (isset($this->items[$offset])) {
            return true;
        }

        return await
            ($this->cache->has($offset));
        //return false;
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
        if (isset($this->items[$offset]) && $item = $this->items[$offset]->get()) {
            return $item;
        }

        return await
            ($this->cache->get($offset));
        //return null;
    }

    /**
     * Sets an item into the cache.
     *
     * @uses \React\Async\await() This method is blocking.
     *
     * @param mixed $offset
     * @param Part  $value
     */
    public function offsetSet($offset, $value): void
    {
        if (array_key_exists($offset, $this->items)) {
            $this->cache->interface->set($this->cache->key_prefix.$offset, $value->serialize());
            $this->items[$offset] = WeakReference::create($value);

            return;
        }

        await
            ($this->cache->set($offset, $value));
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
        if (array_key_exists($offset, $this->items)) {
            $this->cache->interface->delete($this->cache->key_prefix, $offset);
            unset($this->items[$offset]);

            return;
        }

        await
            ($this->cache->delete($offset));
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
                    yield $key => $part;
                } else {
                    //$this->cache->get($key);
                }
            }
        })();
    }

    public function __get(string $key)
    {
        if (in_array($key, ['cache'])) {
            return $this->{$key};
        }
    }
}
