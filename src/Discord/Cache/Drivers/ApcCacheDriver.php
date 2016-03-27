<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Cache\Drivers;

use Discord\Cache\Cache;
use Discord\Cache\CacheInterface;

/**
 * The APC cache driver.
 */
class ApcCacheDriver implements CacheInterface
{
    /**
     * The Cache name.
     *
     * @var string The Cache name.
     */
    public $name = 'apc';

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return apc_fetch($key);
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        if (is_string($ttl)) {
            $ttl = Cache::getDefaultTtl();
        }

        return apc_store($key, $value, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return apc_exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            return apc_delete($key);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        apc_clear_cache();
    }
}
