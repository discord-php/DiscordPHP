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
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Legacy v7.x in memory cache behavior
 *
 * @since 10.0.0
 * @internal
 */
final class LegacyCacheWrapper extends CacheWrapper
{
    /**
     * Repository items array reference.
     *
     * @var ?Part[] Cache Key => Cache Part.
     */
    protected $items;

    /**
     * @param Discord $discord
     * @param array   &$items  Repository items passed by reference.
     * @param string  &$class  Part class name.
     *
     * @internal
     */
    public function __construct(Discord $discord, &$items, string &$class)
    {
        $this->discord = $discord;
        $this->items = &$items;
        $this->class = &$class;

        $this->prefix = '';
    }

    public function __destruct()
    {
    }

    /**
     * Get Part from cache.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return PromiseInterface<mixed>
     */
    public function get($key, $default = null)
    {
        return resolve($this->items[$key] ?? $default);
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
        $this->items[$key] = $value;

        return resolve(true);
    }

    /**
     * Delete Part from cache.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function delete($key)
    {
        if (!array_key_exists($key, $this->items)) {
            return resolve(false);
        }

        unset($this->items[$key]);

        return resolve(true);
    }

    /**
     * Get multiple Parts from cache.
     *
     * @param array $keys
     * @param ?Part $default
     *
     * @return PromiseInterface<array>
     */
    public function getMultiple(array $keys, $default = null)
    {
        $items = [];
        foreach ($keys as $key) {
            $items[$key] = $this->items[$key] ?? $default;
        }

        return resolve($items);
    }

    /**
     * Set multiple Parts into cache.
     *
     * @param Part[] $values
     *
     * @return PromiseInterface<bool>
     */
    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->items[$key] = $value;
        }

        return resolve(true);
    }

    /**
     * Delete multiple Parts from cache.
     *
     * @param array $keys
     *
     * @return PromiseInterface<bool>
     */
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            unset($this->items[$key]);
        }

        return resolve(true);
    }

    /**
     * Clear all Parts from cache.
     *
     * @return PromiseInterface<bool>
     */
    public function clear()
    {
        $this->items = [];

        return resolve(true);
    }

    /**
     * Check if Part is present in cache.
     *
     * @param string $key
     *
     * @return PromiseInterface<bool>
     */
    public function has($key)
    {
        return resolve(array_key_exists($key, $this->items));
    }

    /**
     * @param Part $part
     *
     * @return object
     */
    public function serializer($part)
    {
        return (object) (get_object_vars($part) + ['attributes' => $part->getRawAttributes()]);
    }

    /**
     * @param object $value
     *
     * @return ?Part
     */
    public function unserializer($value)
    {
        if (empty($value->attributes)) {
            $this->discord->getLogger()->warning('Cached Part::$attributes is empty', ['class' => $this->class, 'interface' => 'LEGACY', 'data' => $value]);
        }
        if (empty($value->created)) {
            $this->discord->getLogger()->warning('Cached Part::$created is empty', ['class' => $this->class, 'interface' => 'LEGACY', 'data' => $value]);
        }
        $part = $this->discord->getFactory()->part($this->class, $value->attributes, $value->created);
        foreach ($value as $name => $var) {
            if (!in_array($name, ['created', 'attributes'])) {
                $part->{$name} = $var;
            }
        }

        return $part;
    }
}
