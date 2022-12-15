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
 * @see \Discord\Discord
 * @see CacheWrapper
 *
 * @since 10.0.0
 *
 * @property-read \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface $interface The PSR-16 cache interface.
 * @property-read string                                                      $separator The cache key prefix separator if supported by the interface. Usually dot `.` for generic cache or colon `:` for Redis/Memcached.
 */
class CacheConfig
{
    /**
     * @var \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface
     */
    protected $interface;

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
     * @var string
     */
    protected string $separator = '.';

    /**
     * The default Time To Live for `$interface::set()` and `$interface::setMultiple()`.
     *
     * @var null|int|\DateInterval|float
     */
    public $ttl;

    /**
     * @param \React\Cache\CacheInterface|\Psr\SimpleCache\CacheInterface $interface The PSR-16 Cache Interface.
     * @param bool                                                        $compress  Whether to compress cache data before serialization, ignored in ArrayCache.
     * @param bool                                                        $sweep     Whether to automatically sweep cache.
     * @param string|null                                                 $separator The cache key prefix separator, null for default.
     * @param null|int|\DateInterval|float                                $ttl       The cache time to live default value to pass to the interface.
     */
    public function __construct($interface, bool $compress = false, bool $sweep = false, ?string $separator = null, $ttl = null)
    {
        $this->interface = $interface;
        $this->sweep = $sweep;
        $interfaceName = get_class($interface);
        if (($this->compress = $compress) && stripos($interfaceName, 'Symfony') !== false) {
            trigger_error('Symfony cache is not compatible with option `$compress = true`, Use the DeflateMarshaller to compress data!');
        }
        if (null === $separator) {
            $separator = '.';
            if (stripos($interfaceName, 'Redis') !== false || stripos($interfaceName, 'Memcached') !== false) {
                $separator = ':';
            }
        }
        $this->separator = $separator;
        $this->ttl = $ttl;
    }

    public function __get(string $name)
    {
        if (in_array($name, ['interface', 'separator'])) {
            return $this->$name;
        }
    }
}
