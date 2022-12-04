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

/**
 * Cache configuration class. To be used with Discord `cache` Options.
 *
 * @see Discord
 *
 * @since 10.0.0
 */
class CacheConfig
{
    /**
     * The PSR-16 cache interface.
     *
     * @var \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface
     */
    public $interface;

    /**
     * Whether to compress cache data before serialization, disabled by default, ignored in ArrayCache.
     *
     * @var bool
     */
    public bool $compress = false;

    /**
     * Whether to automatically sweep cached items from memory, disabled by default.
     *
     * @var bool
     */
    public bool $sweep = false;

    /**
     * The cache key prefix separator if supported by the interface.
     *
     * @var string|null Usually dot `.` for generic cache or colon `:` for Redis/Memcached.
     */
    public string $separator;

    /**
     * @param \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface $interface The PSR-16 Cache Interface.
     * @param bool                                                        $compress  Whether to compress cache data before serialization, ignored in ArrayCache.
     * @param bool                                                        $sweep     Whether to automatically sweep cache.
     * @param string|null                                                 $separator The cache key prefix separator.
     */
    public function __construct($interface, bool $compress = false, bool $sweep = false, ?string $separator = null)
    {
        $this->interface = $interface;
        $this->sweep = $sweep;
        $this->compress = $compress;
        if (null === $separator) {
            $separator = '.';
            $interfaceName = get_class($interface);
            if (stripos($interfaceName, 'Redis') !== false || stripos($interfaceName, 'Memcached') !== false) {
                $separator = ':';
            }
        }
        $this->separator = $separator;
    }
}
