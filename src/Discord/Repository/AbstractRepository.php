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

use function React\Promise\reject;
use function React\Promise\resolve;

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
            foreach ($this->items as $key => $value) {
                if ($value === null) {
                    unset($this->items[$key]);
                } elseif (! ($this->items[$key] instanceof WeakReference)) {
                    $this->items[$key] = WeakReference::create($value);
                }
                $this->cache->interface->delete($this->cache->key_prefix.$key);
            }

            return $this->freshenCache($response);
        });
    }

    /**
     * @internal
     */
    protected function freshenCache($response): PromiseInterface
    {
        foreach ($response as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return $this;
        }

        return $this->cache->setMultiple($items)->then(function ($success) {
            return $this;
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

        return $this->http->{$method}($endpoint, $attributes, $headers)->then(function ($response) use ($part) {
            $part->fill((array) $response);
            $part->created = true;

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

            return $this->cache->delete($part->{$this->discrim})->then(function ($success) use ($part) {
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
        if (! $fresh && isset($this->items[$id])) {
            $part = $this->items[$id];
            if ($part instanceof WeakReference) {
                $part = $part->get();
            }

            if ($part) {
                $this->items[$id] = $part;
                return $part;
            }
        }

        if (! isset($this->endpoints['get'])) {
            return reject(new \Exception('You cannot get this part.'));
        }

        $part = $this->factory->part($this->class, [$this->discrim => $id]);
        $endpoint = new Endpoint($this->endpoints['get']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        return $this->http->get($endpoint)->then(function ($response) use ($part, $id) {
            $part->fill(array_merge($this->vars, (array) $response));
            $part->created = true;

            return $this->cache->set($id, $part)->then(function ($success) use ($part) {
                return $part;
            });
        });
    }

    /**
     * {@inheritdoc}
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

            $this->cache->get($key);
            return null;
        }

        foreach ($this->items as $offset => $item) {
            if ($item = $this->offsetGet($offset)) {
                if ($item->{$discrim} == $key) {
                    return $item;
                }
                continue;
            }

            $this->cache->get($offset);
            break;
        }

        return null;
    }

    /**
     * Attempts to get from memory first otherwise load from cache
     *
     * @internal
     *
     * @param string|int $offset
     *
     * @return PromiseInterface<?Part>
     */
    public function cacheGet($offset): PromiseInterface
    {
        return resolve($this->offsetGet($offset) ?? $this->cache->get($offset));
    }

    /**
     * {@inheritdoc}
     */
    public function set($offset, $value)
    {
        if ($this->class === null) {
            return parent::set($offset, $value);
        }

        // Don't insert elements that are not of type class.
        if (! is_a($value, $this->class)) {
            return;
        }

        $this->cache->interface->set($this->cache->key_prefix.$offset, $value->serialize());

        $this->offsetSet($offset, $value);
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->get()` and `$repository->cache->delete()`
     * {@inheritdoc}
     */
    public function pull($key, $default = null)
    {
        if ($item = $this->offsetGet($key)) {
            $default = $item;
            $this->offsetUnset($key);
            $this->cache->interface->delete($this->cache->key_prefix, $key);
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
     * @return PromiseInterface<?Part>
     */
    public function cachePull($key, $default = null): PromiseInterface
    {
        return $this->cacheGet($key)->then(
            fn ($item) => ($item === null) ?
                $default : $this->cache->delete($key)->then(
                    fn ($success) => $item
                )
            );
    }

    /**
     * Pushes items to the repository.
     *
     * @param mixed ...$items
     *
     * @return self
     */
    public function push(...$items): self
    {
        if ($this->class === null) {
            return parent::push($items);
        }

        foreach ($items as $item) {
            if (is_a($item, $this->class)) {
                $key = $item->{$this->discrim};
                $this->items[$key] = $item;
                $this->cache->interface->set($this->cache->key_prefix.$key, $item->serialize());
            }
        }

        return $this;
    }

    /**
     * Pushes a single item to the repository.
     *
     * @deprecated 7.2.0 Use async `$repository->cache->set()`
     *
     * @param Part $item
     *
     * @return self
     */
    public function pushItem($item): self
    {
        if ($this->class === null) {
            return parent::pushItem($item);
        }

        if (is_a($item, $this->class)) {
            $key = $item->{$this->discrim};
            $this->items[$key] = $item;
            $this->cache->interface->set($this->cache->key_prefix.$key, $item->serialize());
        }

        return $this;
    }

    /**
     * Returns the first cached element.
     *
     * @return object|null
     */
    public function first()
    {
        foreach ($this->items as $item) {
            if ($item instanceof WeakReference) {
                $item = $item->get();
            }

            if ($item) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Returns the last cached element.
     *
     * @return object|null
     */
    public function last()
    {
        $items = array_reverse($this->items, true);

        foreach ($items as $item) {
            if ($item instanceof WeakReference) {
                $item = $item->get();
            }

            if ($item) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->has()`
     * {@inheritdoc}
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
            if ($item === null) continue;

            if ($item instanceof WeakReference) {
                $item = $item->get();
            }

            if ($item && $callback($item)) {
                $collection->push($item);
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
            if ($item === null) continue;

            if ($item instanceof WeakReference) {
                $item = $item->get();
            }

            if ($item && $callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->clear()`
     * {@inheritdoc}
     */
    public function clear(): void
    {
        // Set items null but keep the keys to be removed on flush
        $this->items = array_fill_keys(array_keys($this->items), null);
    }

    /**
     * Converts the weak caches to array.
     *
     * @return array
     */
    public function toArray()
    {
        $items = [];

        foreach ($this->items as $key => $item) {
            if ($item instanceof WeakReference) {
                $item = $item->get();
            }
            $items[$key] = $item;
        }

        return $items;
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->has()`
     * {@inheritdoc}
     */
    public function offsetExists($offset): bool
    {
        return parent::offsetExists($offset);
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->get()` or sync `$repository->get()`
     * @internal
     * {@inheritdoc}
     * @return ?Part
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
     * @deprecated 7.2.0 Use async `$repository->cache->set()`
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value): void
    {
        parent::offsetSet($offset, $value);
    }

    /**
     * @deprecated 7.2.0 Use async `$repository->cache->delete()`
     * {@inheritdoc}
     */
    public function offsetUnset($offset): void
    {
        parent::offsetUnset($offset);
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
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return (function () {
            foreach ($this->items as $key => $item) {
                if ($item instanceof WeakReference) {
                    $item = $item->get();
                }

                if ($item) {
                    yield $key => $this->items[$key] = $item;
                } else {
                    $this->cache->get($key);
                }
            }
        })();
    }

    public function __get(string $key)
    {
        if (in_array($key, ['cache'])) {
            return $this->$key;
        }
    }
}
