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

use React\Cache\CacheInterface;
use WeakReference;

/**
 * Wrapper for CacheInterface that tracks Repository items
 *
 * @internal Used by AbstractRepository
 */
class CacheWrapper implements CacheInterface
{
    /**
     * The actual ReactPHP CacheInterface.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Repository items array reference.
     *
     * @var WeakReference[] Cache Key => Cache Weak Reference.
     */
    protected $items;

    /**
     * @param CacheInterface $cacheInterface The actual CacheInterface.
     * @param array          &$items         Repository items passed by reference.
     *
     * @internal
     */
    public function __construct(CacheInterface $cacheInterface, &$items)
    {
        $this->cache = $cacheInterface;
        $this->items = &$items;
    }

    /**
     * @inheritdoc
     */
    public function get($key, $default = null)
    {
        return $this->cache->get($key, $default)->then(function ($value) use ($key) {
            if ($value === null) {
                unset($this->items[$key]);
            } else {
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
        return $this->cache->set($key, $value, $ttl)->then(function ($success) use ($key, $value) {
            if ($success) {
                $this->items[$key] = WeakReference::create($value);
            }

            return $value;
        });
    }

    /**
     * @inheritdoc
     */
    public function delete($key)
    {
        return $this->cache->delete($key)->then(function ($success) use ($key) {
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
        return $this->cache->getMultiple($keys, $default)->then(function ($values) {
            foreach ($values as $key => $value) {
                if ($value === null) {
                    unset($this->items[$key]);
                } else {
                    $this->items[$key] = WeakReference::create($value);
                }
            }

            return $values;
        });
    }

    /**
     * @inheritdoc
     */
    public function setMultiple(array $values, $ttl = null)
    {
        return $this->cache->setMultiple($values, $ttl)->then(function ($success) use ($values) {
            if ($success) {
                foreach ($values as $key => $value) {
                    if ($value !== null) {
                        $this->items[$key] = WeakReference::create($value);
                    }
                }
            }

            return $success;
        });
    }

    /**
     * @inheritdoc
     */
    public function deleteMultiple(array $keys)
    {
        return $this->cache->deleteMultiple($keys)->then(function ($success) use ($keys) {
            if ($success) {
                foreach ($keys as $key => $value) {
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
        return $this->cache->clear()->then(function ($success) {
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
        return $this->cache->has($key)->then(function ($success) use ($key) {
            if (! $success) {
                unset($this->items[$key]);
            }

            return $success;
        });
    }
}
