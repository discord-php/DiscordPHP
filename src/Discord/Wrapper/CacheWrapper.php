<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Wrapper;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Provides an easy wrapper for the Guzzle HTTP client.
 */
class CacheWrapper
{
    /**
     * @var CacheItemPoolInterface
     */
    private $cache;

    /**
     * CacheWrapper constructor.
     *
     * @param CacheItemPoolInterface $cache
     */
    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->cache->hasItem($key);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->cache->getItem($key)->get();
    }

    /**
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return mixed
     */
    public function set($key, $value, $ttl = 300)
    {
        $item = $this->cache->getItem($key);
        $item->set($value);
        $item->expiresAfter($ttl);
        $this->cache->save($item);

        return $item->get();
    }

    /**
     * @param string $key
     */
    public function remove($key)
    {
        $this->cache->deleteItem($key);
    }
}
