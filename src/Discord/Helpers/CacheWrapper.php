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
 * @property-read CacheConfig                                                 $config    Cache configuration.
 */
class CacheWrapper
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
     * The item class name reference.
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
     * Cache configuration.
     *
     * @var CacheConfig
     */
    protected $config;

    /**
     * @param Discord     $discord
     * @param CacheConfig $config  The cache configuration.
     * @param array       &$items  Repository items passed by reference.
     * @param string      &$class  Part class name.
     * @param string[]    $vars    Variable containing hierarchy parent IDs.
     *
     * @internal
     */
    public function __construct(Discord $discord, $config, &$items, string &$class, array $vars)
    {
        $this->discord = $discord;
        $this->config = $config;
        $this->items = &$items;
        $this->class = &$class;

        $this->interface = $config->interface;
        $this->prefix = implode($config->separator, [substr(strrchr($this->class, '\\'), 1)] + $vars).$config->separator;

        if ($config->sweep) {
            // Sweep every heartbeat ack
            $discord->on('heartbeat-ack', [$this, 'sweep']);
        }
    }

    public function __destruct()
    {
        $this->discord->removeListener('heartbeat-ack', [$this, 'sweep']);
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
            if (null === $value) {
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
        $ttl ??= $this->config->ttl;
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
                    if (null === $value) {
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
     * Checks if a value is zlib compressed by checking the magic bytes.
     *
     * @param string $data The data to check.
     *
     * @return bool whether it's zlib compressed data or not.
     *
     * @link https://www.rfc-editor.org/rfc/rfc1950
     *
     * @since 10.0.0
     */
    protected function isZlibCompressed(string $data): bool
    {
        $data = unpack('Ccmf/Cflg', $data);
        $cmethod = $data['cmf'] & 0xF;
        $cinfo = ($data['cmf'] & 0xF0) >> 4;
        // $fcheck = $data['flg'] & 0x1F;
        // $fdict = ($data['flg'] & 0x20) >> 5;
        $flevel = ($data['flg'] & 0xC0) >> 6;

        // Ensure compression method is deflate
        if ($cmethod !== 8) {
            return false;
        }

        // Ensure cinfo <= 32K window size
        if ($cinfo > 7) {
            return false;
        }

        // Ensure [CMF][FLG] is a multiple of 31 as determined by fcheck
        if (($data['cmf'] * 256 + $data['flg']) % 31 !== 0) {
            return false;
        }

        // Ensure valid compression level
        if ($flevel > 3) {
            return false;
        }

        return true;
    }

    /**
     * @param Part $part
     *
     * @return object|string
     */
    public function serializer($part)
    {
        $data = (object) (get_object_vars($part) + ['attributes' => $part->getRawAttributes()]);

        if (! ($this->interface instanceof ArrayCache)) {
            if ($this->interface instanceof \React\Cache\CacheInterface) {
                $data = serialize($data);
            }

            if ($this->config->compress) {
                $data = zlib_encode($data, ZLIB_ENCODING_DEFLATE);
            }
        }

        return $data;
    }

    /**
     * @param string $value
     *
     * @return ?Part
     */
    public function unserializer($value)
    {
        if (! ($this->interface instanceof ArrayCache)) {
            if ($this->isZlibCompressed($value)) {
                $value = zlib_decode($value);
            }

            if ($this->interface instanceof \React\Cache\CacheInterface) {
                $tmp = unserialize($value);
                if ($tmp === false) {
                    $this->discord->getLogger()->error('Malformed cache serialization', ['class' => $this->class, 'interface' => get_class($this->interface), 'serialized' => $value]);

                    return null;
                }
                $value = $tmp;
            }
        }

        if (empty($value->attributes)) {
            $this->discord->getLogger()->warning('Cached Part::$attributes is empty', ['class' => $this->class, 'interface' => get_class($this->interface), 'data' => $value]);
        }
        if (empty($value->created)) {
            $this->discord->getLogger()->warning('Cached Part::$created is empty', ['class' => $this->class, 'interface' => get_class($this->interface), 'data' => $value]);
        }
        $part = $this->discord->getFactory()->part($this->class, $value->attributes, $value->created);
        foreach ($value as $name => $var) {
            if (! in_array($name, ['created', 'attributes'])) {
                $part->{$name} = $var;
            }
        }

        return $part;
    }

    /**
     * Prune deleted items from cache and weaken items. Items with Bot's ID are
     * exempt.
     *
     * @return int Pruned items.
     */
    public function sweep(): int
    {
        $pruning = 0;
        foreach ($this->items as $key => $item) {
            if (null === $item) {
                // Item was removed from memory, delete from cache
                $this->delete($key);
                $pruning++;
            } elseif ($item instanceof Part) {
                // Skip ID related to Bot
                if ($key != $this->discord->id) {
                    // Item is no longer used other than in the repository, weaken so it can be garbage collected
                    $this->items[$key] = WeakReference::create($item);
                }
            }
        }
        if ($pruning) {
            $this->discord->getLogger()->debug('Pruning repository cache', ['count' => $pruning, 'class' => $this->class]);
        }

        return $pruning;
    }

    public function __get(string $name)
    {
        if (in_array($name, ['interface', 'config'])) {
            return $this->$name;
        }
    }
}
