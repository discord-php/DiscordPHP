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
use React\Cache\CacheInterface;
use WeakReference;

/**
 * Wrapper for CacheInterface that tracks Repository items
 *
 * @internal Used by AbstractRepository
 */
class CacheWrapper
{
    /**
     * @var Discord
     */
    protected $discord;

    /**
     * The actual ReactPHP CacheInterface.
     *
     * @var CacheInterface
     */
    public $interface;

    /**
     * Repository items array reference.
     *
     * @var WeakReference[] Cache Key => Cache Weak Reference.
     */
    protected $items;

    /**
     * The allowed class name to be unserialized
     *
     * @var string
     */
    protected $class;

    /**
     * @var string
     */
    public $keyPrefix;

    /**
     * @param CacheInterface $cacheInterface The actual CacheInterface.
     * @param array          &$items         Repository items passed by reference.
     * @param string         $class          Object class name allowed for serialization.
     *
     * @internal
     */
    public function __construct(Discord $discord, CacheInterface $cacheInterface, &$items, string $class)
    {
        $this->discord = $discord;
        $this->interface = $cacheInterface;
        $this->items = &$items;
        $this->class = $class;

        $this->keyPrefix = substr(strrchr($this->class, '\\'), 1) . '.';
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        return $this->interface->get($this->keyPrefix.$key, $default)->then(function ($value) use ($key) {
            if ($value === null) {
                unset($this->items[$key]);
            } else {
                /** @var Part */
                $value = unserialize($value, ['allowed_classes' => [$this->class]]);
                $value->created = true;
                $value->initDiscord($this->discord);
                $this->items[$key] = WeakReference::create($value);
            }

            return $value;
        });
    }

    /**
     * @inheritdoc
     */
    public function set($key, $value, $ttl = null)
    {
        return $this->interface->set($this->keyPrefix.$key, serialize($value), $ttl)->then(function ($success) use ($key, $value) {
            if ($success) {
                $this->items[$key] = WeakReference::create($value);
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        return $this->interface->delete($this->keyPrefix.$key)->then(function ($success) use ($key) {
            if ($success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function getMultiple(array $keys, $default = null)
    {
        $realKeys = array_map(function ($key) {
            return $this->keyPrefix.$key;
        }, $keys);

        return $this->interface->getMultiple($realKeys, $default)->then(function ($values) use ($keys) {
            foreach ($keys as $key) {
                // Check if the prefixed key is returned
                if (! array_key_exists($this->keyPrefix.$key, $values)) {
                    unset($this->items[$key]);
                    continue;
                }

                // Get real value from prefixed key
                $value = $values[$this->keyPrefix.$key];

                if ($value === null) {
                    unset($this->items[$key]);
                } else {
                    /** @var Part */
                    $values[$key] = unserialize($value, ['allowed_classes' => [$this->class]]);
                    $values[$key]->created = true;
                    $values[$key]->initDiscord($this->discord);
                    $this->items[$key] = WeakReference::create($values[$key]);
                }

                // Remove real value with key prefix
                unset($values[$this->keyPrefix.$key]);
            }

            return $values;
        });
    }

    /**
     * @inheritdoc
     */
    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            $valueRefs[$key] = WeakReference::create($value);

            // Replace values key with prefixed key
            $values[$this->keyPrefix.$key] = serialize($value);
            unset($values[$key]);
        }

        return $this->interface->setMultiple($values, $ttl)->then(function ($success) use ($valueRefs) {
            if ($success) {
                $this->items = array_merge($this->items, $valueRefs);
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function deleteMultiple(array $keys)
    {
        $realKeys = array_map(function ($key) {
            return $this->keyPrefix.$key;
        }, $keys);

        return $this->interface->deleteMultiple($realKeys)->then(function ($success) use ($keys) {
            if ($success) {
                foreach ($keys as $key) {
                    unset($this->items[$key]);
                }
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $realKeys = array_map(function ($key) {
            return $this->keyPrefix.$key;
        }, $this->items);

        return $this->interface->deleteMultiple($realKeys)->then(function ($success) {
            if ($success) {
                $this->items = [];
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function has($key)
    {
        return $this->interface->has($this->keyPrefix.$key)->then(function ($success) use ($key) {
            if (! $success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }
}
