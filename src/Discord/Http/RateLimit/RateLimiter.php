<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Http\RateLimit;

use Discord\Wrapper\CacheWrapper;
use GuzzleHttp\Psr7\Request;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

class RateLimiter
{
    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface Event loop.
     */
    protected $loop;

    /**
     * The cache.
     *
     * @var CacheWrapper Cache.
     */
    protected $cache;

    /**
     * Array of buckets.
     *
     * @var array Buckets.
     */
    protected $buckets = [];

    /**
     * Constructs a rate limiter.
     *
     * @param LoopInterface $loop The ReactPHP event loop.
     */
    public function __construct(LoopInterface $loop, CacheWrapper $cache)
    {
        $this->loop  = $loop;
        $this->cache = $cache;

        $this->addBuckets();
    }

    /**
     * Handles a request to the rate limiter.
     *
     * @param Request $request The request to check.
     *
     * @return \React\Promise\Promise
     */
    public function handle(Request $request)
    {
        $deferred = new Deferred();

        $uri = $request->getUri();

        return $deferred->promise();
    }

    /**
     * Adds the buckets.
     *
     * @return void
     */
    protected function addBuckets()
    {
        // Global Bucket
        $this->buckets['global'] = new GlobalBucket($this->loop);
    }
}
