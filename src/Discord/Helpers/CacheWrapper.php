<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Discord\Discord;
use Discord\Parts\Part;
use React\Cache\ArrayCache;
use React\Promise\PromiseInterface;
use Throwable;
use WeakReference;

use function React\Promise\all;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * Wrapper for CacheInterface that store Repository items.
 *
 * Compatible with react/cache 0.5 - 1.x and psr/simple-cache interface.
 *
 * @since 10.0.0
 *
 * @property-read \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface $interface The actual ReactPHP PSR-16 CacheInterface.
 *
 * @internal
 */
final class CacheWrapper
{
    /**
     * @var Discord
     */
    protected $discord;

    /**
     * @var \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface
     */
    protected $interface;

    /**
     * Repository items array reference.
     *
     * @var ?Part[]|WeakReference[] Cache Key => Cache Part.
     */
    protected $items;

    /**
     * The item class name.
     *
     * @var string
     */
    protected $class;

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * @var ?callable Sweeper callback
     */
    protected $sweeper;

    /**
     * @param Discord                                                     $discord
     * @param \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface $cacheInterface The actual CacheInterface.
     * @param array                                                       &$items         Repository items passed by reference.
     * @param string                                                      &$class         Part class name.
     * @param string[]                                                    $vars           Variable containing hierarchy parent IDs.
     *
     * @internal
     */
    public function __construct(Discord $discord, $cacheInterface, &$items, string &$class, array $vars)
    {
        $this->discord = $discord;
        $this->interface = $cacheInterface;
        $this->items = &$items;
        $this->class = &$class;

        $separator = '.';
        $cacheInterfaceName = get_class($cacheInterface);
        if (stripos($cacheInterfaceName, 'Redis') !== false || stripos($cacheInterfaceName, 'Memcached') !== false) {
            $separator = ':';
        }

        $this->prefix = implode($separator, [substr(strrchr($this->class, '\\'), 1)] + $vars).$separator;

        if ($discord->options['cacheSweep']) {
            // Sweep every heartbeat ack
            $this->sweeper = function ($time, Discord $discord) {
                $flushing = 0;
                foreach ($this->items as $key => $item) {
                    if ($item === null) {
                        // Item was removed from memory, delete from cache
                        $this->delete($key);
                        $flushing++;
                    } elseif ($item instanceof Part) {
                        // Skip ID related to Bot
                        if ($key != $discord->id) {
                            // Item is no longer used other than in the repository, weaken so it can be garbage collected
                            $this->items[$key] = WeakReference::create($item);
                        }
                    }
                }
                if ($flushing) {
                    $this->discord->getLogger()->debug('Flushing repository cache', ['count' => $flushing, 'class' => $this->class]);
                }
            };
            $discord->on('heartbeat-ack', $this->sweeper);
        }
    }

    public function __destruct()
    {
        if ($this->sweeper) {
            $this->discord->removeListener('heartbeat-ack', $this->sweeper);
        }
    }

    /**
     * Get Part from cache.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return PromiseInterface<Part>
     */
    public function get($key, $default = null)
    {
        $handleValue = function ($value) use ($key) {
            if ($value === null) {
                unset($this->items[$key]);
            } else {
                $value = $this->items[$key] = $this->unserializer($value);
            }

            return $value;
        };

        try {
            $result = $this->interface->get($this->prefix.$key, $default);
        } catch (Throwable $throwable) {
            return reject($throwable);
        }

        if ($result instanceof PromiseInterface) {
            return $result->then($handleValue);
        }

        return resolve($handleValue($result));
    }

    /**
     * Set Part into cache.
     *
     * @param string $key
     * @param Part   $value
     *
     * @return PromiseInterface<bool>
     */
    public function set($key, $value, $ttl = null)
    {
        $item = $this->serializer($value);

        $handleValue = function ($success) use ($key, $value) {
            if ($success) {
                $this->items[$key] = $value;
            }

            return $success;
        };

        try {
            $result = $this->interface->set($this->prefix.$key, $item, $ttl);
        } catch (Throwable $throwable) {
            return reject($throwable);
        }

        if ($result instanceof PromiseInterface) {
            return $result->then($handleValue);
        }

        return resolve($handleValue($result));
    }

    /**
     * Delete Part from cache.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>|bool
     */
    public function delete($key)
    {
        $handleValue = function ($success) use ($key) {
            if ($success) {
                unset($this->items[$key]);
            }

            return $success;
        };

        try {
            $result = $this->interface->delete($this->prefix.$key);
        } catch (Throwable $throwable) {
            return reject($throwable);
        }

        if ($result instanceof PromiseInterface) {
            return $result->then($handleValue);
        }

        return resolve($handleValue($result));
    }

    /**
     * Get multiple Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param array $keys
     * @param ?Part $default
     *
     * @return PromiseInterface<array>
     */
    public function getMultiple(array $keys, $default = null)
    {
        if (is_callable([$this->interface, 'getMultiple'])) {
            $handleValue = function ($values) {
                foreach ($values as $key => &$value) {
                    if ($value === null) {
                        unset($this->items[$key]);
                    } else {
                        $value = $this->items[$key] = $this->unserializer($value);
                    }
                }

                return $values;
            };

            try {
                $result = $this->interface->getMultiple(array_map(fn ($key) => $this->prefix.$key, $keys), $default);
            } catch (Throwable $throwable) {
                return reject($throwable);
            }

            if ($result instanceof PromiseInterface) {
                return $result->then($handleValue);
            }

            return resolve($handleValue($result));
        }

        $promises = [];

        foreach ($keys as $key) {
            $promises[$key] = $this->get($key, $default);
        }

        return all($promises);
    }

    /**
     * Set multiple Parts into cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param Part[] $values
     * @param ?int   $ttl
     *
     * @return PromiseInterface<bool>
     */
    public function setMultiple(array $values, $ttl = null)
    {
        $promises = [];

        foreach ($values as $key => $value) {
            $promises[$key] = $this->set($key, $value, $ttl);
        }

        return all($promises);
    }

    /**
     * Delete multiple Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param array $keys
     *
     * @return PromiseInterface<bool>
     */
    public function deleteMultiple(array $keys)
    {
        if (is_callable([$this->interface, 'deleteMultiple'])) {
            $handleValue = function ($success) use ($keys) {
                if ($success) {
                    foreach ($keys as $key) {
                        unset($this->items[$key]);
                    }
                }

                return $success;
            };

            $keys = array_map(fn ($key) => $this->prefix.$key, $keys);

            try {
                $result = $this->interface->deleteMultiple($keys);
            } catch (Throwable $throwable) {
                return reject($throwable);
            }

            if ($result instanceof PromiseInterface) {
                return $result->then($handleValue);
            }

            return resolve($handleValue($result));
        }

        $promises = [];

        foreach ($keys as $key) {
            $promises[$key] = $this->delete($key);
        }

        return all($promises);
    }

    /**
     * Clear all Parts from cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @return PromiseInterface<bool>
     */
    public function clear()
    {
        return $this->deleteMultiple(array_keys($this->items));
    }

    /**
     * Check if Part is present in cache.
     *
     * For react/cache 0.5 polyfill.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function has($key)
    {
        $handleValue = function ($value) use ($key) {
            if (! $value) {
                unset($this->items[$key]);
            }

            return (bool) $value;
        };

        try {
            if (is_callable([$this->interface, 'has'])) {
                $result = $this->interface->has($this->prefix.$key);
            } else {
                $result = $this->get($this->prefix.$key);
            }
        } catch (Throwable $throwable) {
            return reject($throwable);
        }

        if ($result instanceof PromiseInterface) {
            return $result->then($handleValue);
        }

        return resolve($handleValue($result));
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param Part $part
     *
     * @return object|string
     */
    public function serializer($part)
    {
        $data = (object) (get_object_vars($part) + ['attributes' => $part->getRawAttributes()]);

        if ($this->interface instanceof \React\Cache\CacheInterface && ! ($this->interface instanceof ArrayCache)) {
            return serialize($data);
        }

        return $data;
    }

    /**
     * @param string $value
     *
     * @return Part
     */
    public function unserializer($value)
    {
        if ($this->interface instanceof \React\Cache\CacheInterface && ! ($this->interface instanceof ArrayCache)) {
            $value = unserialize($value);
        }

        $part = $this->discord->getFactory()->part($this->class, $value->attributes, $value->created);
        foreach ($value as $name => $var) {
            if (! in_array($name, ['created', 'attributes'])) {
                $part->{$name} = $var;
            }
        }

        return $part;
    }

    public function __get(string $name)
    {
        if (in_array($name, ['interface'])) {
            return $this->$name;
        }
    }
}
