<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Factory;

use Discord\Discord;
use Discord\Http\Http;
use Discord\Wrapper\CacheWrapper;

/**
 * Exposes an interface to build part objects without the other requirements.
 */
class Factory
{
    /**
     * The Discord client.
     *
     * @var Discord Client.
     */
    protected $discord;

    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The cache.
     *
     * @var CacheWrapper Cache.
     */
    protected $cache;
    /**
     * Constructs a factory.
     *
     * @param Discord      $discord The Discord client.
     * @param Http         $http    The HTTP client.
     * @param CacheWrapper $cache   The cache.
     */
    public function __construct(Discord $discord, Http $http, CacheWrapper $cache)
    {
        $this->discord = $discord;
        $this->http    = $http;
        $this->cache   = $cache;
    }

    /**
     * Creates an object.
     *
     * @param string $class   The class to build.
     * @param array  $data    Data to create the object.
     * @param bool   $created Whether the object is created (if part).
     *
     * @return mixed The object.
     */
    public function create($class, $data = [], $created = false)
    {
        if (! is_array($data)) {
            $data = (array) $data;
        }

        if (strpos($class, 'Discord\\Parts') !== false) {
            $object = new $class($this, $this->discord, $this->http, $this->cache, $data, $created);
        } elseif (strpos($class, 'Discord\\Repository') !== false) {
            $object = new $class($this->http, $this->cache, $this, $data);
        } else {
            throw new \Exception('The class '.$class.' is not a Part or a Repository.');
        }

        return $object;
    }
}
