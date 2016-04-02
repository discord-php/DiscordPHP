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
use Predis\Client;

/**
 * The Redis cache driver.
 */
class RedisCacheDriver implements CacheInterface
{
    /**
     * The Predis client.
     *
     * @var Client The Predis client.
     */
    protected $redis;

    /**
     * The Cache name.
     *
     * @var string The Cache name.
     */
    public $name = 'redis';

    /**
     * Constructs a Redis cache driver instance.
     *
     * @param string $hostname The Redis hostname to connect to.
     * @param int    $port     The Redis port to connect to.
     * @param string $password The Redis server password, if applicable.
     * @param int    $db       The Database to use.
     *
     * @return void
     */
    public function __construct($hostname, $port = 6379, $password = null, $db = 0)
    {
        $this->redis = new Client([
            'scheme'   => 'tcp',
            'host'     => $hostname,
            'port'     => $port,
            'database' => $db,

            'prefix' => 'discordphp:',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return unserialize($this->redis->get($key));
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value, $ttl = null)
    {
        $value = serialize($value);
        $this->redis->set($key, $value);

        if (is_string($ttl)) {
            $ttl = Cache::getDefaultTtl();
        }

        if (! is_null($ttl)) {
            $this->redis->expire($key, $ttl);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return $this->redis->exists($key);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key)
    {
        if ($this->has($key)) {
            $this->redis->del($key);

            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->redis->flushdb();
    }
}
