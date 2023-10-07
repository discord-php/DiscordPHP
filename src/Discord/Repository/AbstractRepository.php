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
use Traversable;
use WeakReference;

use function Discord\nowait;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Repositories provide a way to store and update parts on the Discord server.
 *
 * @since 4.0.0
 *
 * @author Aaron Scherer <aequasi@gmail.com>
 * @author David Cole <david.cole1340@gmail.com>
 *
 * @property-read string       $discrim The discriminator.
 * @property-read CacheWrapper $cache   The react/cache wrapper.
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
     * @param Discord $discord
     * @param array   $vars    An array of variables used for the endpoint.
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $this->http = $discord->getHttpClient();
        $this->factory = $discord->getFactory();
        $this->vars = $vars;
        $this->cache = new CacheWrapper($discord, $discord->getCacheConfig(static::class), $this->items, $this->class, $this->vars);

        parent::__construct([], $this->discrim, $this->class);
    }

    /**
     * Freshens the repository cache.
     *
     * @param array $queryparams Query string params to add to the request (no validation)
     *
     * @return ExtendedPromiseInterface<static>
     *
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
            foreach ($this->items as $offset => $value) {
                if ($value === null) {
                    unset($this->items[$offset]);
                } elseif (! ($this->items[$offset] instanceof WeakReference)) {
                    $this->items[$offset] = WeakReference::create($value);
                }
                $this->cache->interface->delete($this->cache->getPrefix().$offset);
            }

            return $this->cacheFreshen($response);
        });
    }

    /**
     * @param object $response
     *
     * @return ExtendedPromiseInterface<static>
     */
    protected function cacheFreshen($response): ExtendedPromiseInterface
    {
        foreach ($response as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        return $this->cache->setMultiple($items)->then(fn ($success) => $this);
    }

    /**
     * Builds a new, empty part.
     *
     * @param array|object $attributes The attributes for the new part.
     * @param bool         $created
     *
     * @return Part The new part.
     *
     * @throws \Exception
     */
    public function create(array|object $attributes = [], bool $created = false): Part
    {
        $attributes = array_merge((array) $attributes, $this->vars);

        return $this->factory->part($this->class, $attributes, $created);
    }

    /**
     * Attempts to save a part to the Discord servers.
     *
     * @param Part        $part   The part to save.
     * @param string|null $reason Reason for Audit Log (if supported).
     *
     * @return ExtendedPromiseInterface<Part>
     *
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

        return $this->http->{$method}($endpoint, $attributes, $headers)->then(function ($response) use ($part) {
            $part->created = true;
            $part->fill((array) $response);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Attempts to delete a part on the Discord servers.
     *
     * @param Part|string $part   The part to delete.
     * @param string|null $reason Reason for Audit Log (if supported).
     *
     * @return ExtendedPromiseInterface<Part>
     *
     * @throws \Exception
     */
    public function delete($part, ?string $reason = null): ExtendedPromiseInterface
    {
        if (! isset($part)) {
            return reject(new \Exception('You cannot delete a non-existant part.'));
        }

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

            return $this->cache->delete($part->{$this->discrim})->then(fn ($success) => $part);
        });
    }

    /**
     * Returns a part with fresh values.
     *
     * @param Part  $part        The part to get fresh values.
     * @param array $queryparams Query string params to add to the request (no validation)
     *
     * @return ExtendedPromiseInterface<Part>
     *
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

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
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
            if (isset($this->items[$id])) {
                $part = $this->items[$id];
                if ($part instanceof WeakReference) {
                    $part = $part->get();
                }

                if ($part) {
                    $this->items[$id] = $part;

                    return resolve($part);
                }
            } else {
                return $this->cache->get($id)->then(function ($part) use ($id) {
                    if ($part === null) {
                        return $this->fetch($id, true);
                    }

                    return $part;
                });
            }
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $part = $this->factory->part($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        return $this->http->get($endpoint)->then(function ($response) use ($part, $id) {
            $part->created = true;
            $part->fill(array_merge($this->vars, (array) $response));

            return $this->cache->set($id, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Gets a part from the repository.
     *
     * @param string $discrim
     * @param mixed  $key
     *
     * @return Part|null
     */
    public function get(string $discrim, $key)
    {
        if ($key === null) {
            return null;
        }

        if ($discrim == $this->discrim) {
            if ($item = $this->offsetGet($key)) {
                return $item;
            }

            // Attempt to get resolved value if promise is resolved without waiting
            return nowait($this->cache->get($key));
        }

        foreach ($this->items as $offset => $item) {
            if ($item = $this->offsetGet($offset)) {
                if ($item->{$discrim} == $key) {
                    return $item;
                }
                continue;
            }

            $resolved = nowait($this->cache->get($offset));
            if ($resolved !== null) {
                return $resolved;
            }
            break;
        }

        return null;
    }

    /**
     * Attempts to get from memory first otherwise load from cache.
     *
     * @internal
     *
     * @param string|int $offset
     *
     * @return ExtendedPromiseInterface<?Part>
     */
    public function cacheGet($offset): ExtendedPromiseInterface
    {
        return resolve($this->offsetGet($offset) ?? $this->cache->get($offset));
    }

    /**
     * Sets a part in the repository.
     *
     * @param string|int $offset
     * @param Part       $value
     */
    public function set($offset, $value)
    {
        // Don't insert elements that are not of type class.
        if (! is_a($value, $this->class)) {
            return;
        }

        $this->cache->interface->set($this->cache->getPrefix().$offset, $this->cache->serializer($value), $this->cache->config->ttl);
        $this->items[$offset] = $value;
    }

    /**
     * Pulls a part from the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cachePull()`
     *
     * @param string|int $key
     * @param mixed      $default
     *
     * @return Part|mixed
     */
    public function pull($key, $default = null)
    {
        if ($item = $this->offsetGet($key)) {
            $default = $item;
            unset($this->items[$key]);
            $this->cache->interface->delete($this->cache->getPrefix().$key);
        }

        return $default;
    }

    /**
     * Pulls an item from cache.
     *
     * @internal
     *
     * @param string|int $key
     * @param ?Part      $default
     *
     * @return ExtendedPromiseInterface<?Part>
     */
    public function cachePull($key, $default = null): ExtendedPromiseInterface
    {
        return $this->cacheGet($key)->then(fn ($item) => ($item === null) ? $default : $this->cache->delete($key)->then(fn ($success) => $item));
    }

    /**
     * Pushes a single item to the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->set()`
     *
     * @param Part $item
     *
     * @return $this
     */
    public function pushItem($item): self
    {
        if (is_a($item, $this->class)) {
            $key = $item->{$this->discrim};
            $this->items[$key] = $item;
            $this->cache->interface->set($this->cache->getPrefix().$key, $this->cache->serializer($item), $this->cache->config->ttl);
        }

        return $this;
    }

    /**
     * Returns the first cached part.
     *
     * @return Part|null
     */
    public function first()
    {
        foreach ($this->items as $offset => $item) {
            if ($item instanceof WeakReference) {
                if (! $item = $item->get()) {
                    // Attempt to get resolved value if promise is resolved without waiting
                    $item = nowait($this->cache->get($offset));
                }
            }

            if ($item) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Returns the last cached part.
     *
     * @return Part|null
     */
    public function last()
    {
        $items = array_reverse($this->items, true);

        foreach ($items as $offset => $item) {
            if ($item instanceof WeakReference) {
                if (! $item = $item->get()) {
                    // Attempt to get resolved value if promise is resolved without waiting
                    $item = nowait($this->cache->get($offset));
                }
            }

            if ($item) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Checks if the array has an object.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->has()`
     *
     * @param array ...$keys
     *
     * @return bool
     */
    public function has(...$keys): bool
    {
        foreach ($keys as $key) {
            if (! isset($this->items[$key]) || nowait($this->cache->has($key)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Runs a filter callback over the repository and returns a new collection
     * based on the response of the callback.
     *
     * @param callable $callback
     *
     * @return Collection
     */
    public function filter(callable $callback): Collection
    {
        $collection = new Collection([], $this->discrim, $this->class);

        foreach ($this->items as $offset => $item) {
            if ($item instanceof WeakReference) {
                if (! $item = $item->get()) {
                    // Attempt to get resolved value if promise is resolved without waiting
                    $item = nowait($this->cache->get($offset));
                }
            }

            if ($item === null) {
                continue;
            }

            if ($callback($item)) {
                $collection->push($item);
            }
        }

        return $collection;
    }

    /**
     * Runs a filter callback over the repository and returns the first part
     * where the callback returns `true` when given the part.
     *
     * @param callable $callback
     *
     * @return Part|null `null` if no items returns `true` when called in the callback.
     */
    public function find(callable $callback)
    {
        foreach ($this->getIterator() as $item) {
            if ($item === null) {
                continue;
            }

            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Clears the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->clear()`
     */
    public function clear(): void
    {
        // Set items null but keep the keys to be removed on prune
        $this->items = array_fill_keys(array_keys($this->items), null);
    }

    /**
     * Converts the weak caches to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $items = [];

        foreach ($this->items as $offset => $item) {
            if ($item instanceof WeakReference) {
                $item = $item->get();
            }
            $items[$offset] = $item;
        }

        return $items;
    }

    /**
     * Get the keys of the items.
     *
     * @return int[]|string[]
     */
    public function keys(): array
    {
        return array_keys($this->items);
    }

    /**
     * If the repository has an offset.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->has()`
     *
     * @param string|int $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return parent::offsetExists($offset);
    }

    /**
     * Gets a part from the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cacheGet()` or sync `$repository->get()`
     *
     * @param string|int $offset
     *
     * @return Part|null
     */
    public function offsetGet($offset)
    {
        $item = parent::offsetGet($offset);

        if ($item instanceof WeakReference) {
            $item = $item->get();
        }

        if ($item) {
            return $this->items[$offset] = $item;
        }

        return null;
    }

    /**
     * Sets a part into the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->set()`
     *
     * @param string|int $offset
     * @param ?Part      $value
     */
    public function offsetSet($offset, $value): void
    {
        parent::offsetSet($offset, $value);
    }

    /**
     * Unsets an index from the repository.
     *
     * @deprecated 10.0.0 Use async `$repository->cache->delete()`
     *
     * @param string|int offset
     */
    public function offsetUnset($offset): void
    {
        parent::offsetUnset($offset);
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Returns an iterator for the cache.
     *
     * @return Traversable
     */
    public function &getIterator(): Traversable
    {
        foreach ($this->items as $offset => &$item) {
            if ($item instanceof WeakReference) {
                // Attempt to get resolved value if promise is resolved without waiting
                $item = $item->get() ?? nowait($this->cache->get($offset));
            }

            yield $offset => $item;
        }
    }

    public function __get(string $key)
    {
        if (in_array($key, ['discrim', 'cache'])) {
            return $this->$key;
        }
    }
}
