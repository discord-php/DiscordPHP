<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Cache;

/**
 * CacheInterface defines an interface for interacting with objects inside a cache.
 */
interface CacheInterface
{
    /**
     * Attempts to get an item in the Cache and returns it.
     *
     * @param mixed $key The item key.
     *
     * @return mixed The item in Cache or null.
     */
    public function get($key);

    /**
     * Sets an item in the Cache.
     *
     * @param mixed $key   The key to place the value at.
     * @param mixed $value The value to set.
     * @param int   $ttl   The time the Cache item has to live.
     *
     * @return bool Whether setting the item succeeded or failed.
     */
    public function set($key, $value, $ttl = null);

    /**
     * Checks if a key is set in the Cache.
     *
     * @param mixed $key The key to check.
     *
     * @return bool Whether the key exists.
     */
    public function has($key);

    /**
     * Unsets a key from the Cache.
     *
     * @param mixed $key The key to unset.
     *
     * @return bool Whether the key was unset.
     */
    public function remove($key);

    /**
     * Clears all items from the Cache.
     *
     * @return void
     */
    public function clear();
}
