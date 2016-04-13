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

use React\EventLoop\LoopInterface;
use React\Promise\Deferred;

abstract class AbstractBucket
{
    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface Event loop.
     */
    protected $loop;

    /**
     * How many requests the bucket can handle
     * within the time specified in $time.
     *
     * @var int Total requests.
     */
    protected $uses;

    /**
     * How often the bucket resets.
     *
     * @var int Bucket reset time.
     */
    protected $time;

    /**
     * The current use count.
     *
     * @var int How many requests have been run so far.
     */
    protected $currentCount = 0;

    /**
     * Array of current promises.
     *
     * @var array Promise array.
     */
    protected $promises = [];

    /**
     * Constructs a Rate Limit bucket.
     *
     * @param LoopInterface $loop The ReactPHP event loop.
     *
     * @return void
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;

        $this->loop->addPeriodicTimer($this->time, function () {
            $this->currentCount = 0;

            $promises = $this->promises;
            $this->promises = [];

            foreach ($promises as $deferred) {
                $this->handle()->then(function () use ($deferred) {
                    $deferred->resolve();
                });
            }
        });
    }

    /**
     * Handles a request on the bucket.
     *
     * @return \React\Promise\Promise
     */
    public function handle()
    {
        $deferred = new Deferred();

        if ($this->currentCount >= $this->uses) {
            $this->promises[] = $deferred;
        } else {
            ++$this->currentCount;
            $deferred->resolve();
        }

        return $deferred->promise();
    }
}
