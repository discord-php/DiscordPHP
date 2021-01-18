<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use SplQueue;

/**
 * Represents a rate-limit bucket.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Bucket
{
    /**
     * Request queue.
     *
     * @var SplQueue
     */
    protected $queue;

    /**
     * Bucket name.
     *
     * @var string
     */
    protected $name;

    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * HTTP logger.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Callback for when a request is ready.
     *
     * @var callable
     */
    protected $runRequest;

    /**
     * Whether we are checking the queue.
     *
     * @var bool
     */
    protected $checkerRunning = false;

    /**
     * Number of requests allowed before reset.
     *
     * @var int
     */
    protected $requestLimit;

    /**
     * Number of remaining requests before reset.
     *
     * @var int
     */
    protected $requestRemaining;

    /**
     * Timer to reset the bucket.
     *
     * @var TimerInterface
     */
    protected $resetTimer;

    /**
     * Bucket constructor.
     *
     * @param string   $name
     * @param callable $runRequest
     */
    public function __construct(string $name, LoopInterface $loop, LoggerInterface $logger, callable $runRequest)
    {
        $this->queue = new SplQueue;
        $this->name = $name;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->runRequest = $runRequest;
    }

    /**
     * Enqueue a request.
     *
     * @param Request $request
     */
    public function enqueue(Request $request)
    {
        $this->queue->enqueue($request);
        $this->checkQueue();
    }

    /**
     * Checks for requests in the bucket.
     */
    public function checkQueue()
    {
        // We are already checking the queue.
        if ($this->checkerRunning) {
            return;
        }

        $checkQueue = function () use (&$checkQueue) {
            // Check for rate-limits
            if ($this->requestRemaining < 1 && ! is_null($this->requestRemaining)) {
                $this->logger->info($this.' expecting rate limit, timer interval '.(($this->resetTimer->getInterval() ?? 0) * 1000).' ms');
                $this->checkerRunning = false;

                return;
            }

            // Queue is empty, job done.
            if ($this->queue->isEmpty()) {
                $this->checkerRunning = false;

                return;
            }

            /** @var Request */
            $request = $this->queue->dequeue();
            $request->getDeferred()->promise()->otherwise(function () use ($checkQueue) {
                // exception happened - move on to next request
                $checkQueue();
            });

            ($this->runRequest)($request)->done(function (ResponseInterface $response) use (&$checkQueue) {
                $resetAfter = (float) $response->getHeaderLine('X-Ratelimit-Reset-After');
                $limit = $response->getHeaderLine('X-Ratelimit-Limit');
                $remaining = $response->getHeaderLine('X-Ratelimit-Remaining');

                if ($resetAfter) {
                    $resetAfter = (float) $resetAfter;

                    if ($this->resetTimer) {
                        $this->loop->cancelTimer($this->resetTimer);
                    }

                    $this->resetTimer = $this->loop->addTimer($resetAfter, function () {
                        // Reset requests remaining and check queue
                        $this->requestRemaining = $this->requestLimit;
                        $this->resetTimer = null;
                        $this->checkQueue();
                    });
                }

                // Check if rate-limit headers are present and store
                if (is_numeric($limit)) {
                    $this->requestLimit = (int) $limit;
                }

                if (is_numeric($remaining)) {
                    $this->requestRemaining = (int) $remaining;
                }

                // Check for more requests
                $checkQueue();
            }, function (RateLimit $rateLimit) use (&$checkQueue, $request) {
                $this->queue->enqueue($request);

                // Bucket-specific rate-limit
                // Re-queue the request and wait the retry after time
                if (! $rateLimit->isGlobal()) {
                    $this->loop->addTimer($rateLimit->getRetryAfter() / 1000, $checkQueue);
                }
                // Stop the queue checker for a global rate-limit.
                // Will be restarted when global rate-limit finished.
                else {
                    $this->checkerRunning = false;
                }
            });
        };

        $this->checkerRunning = true;
        $checkQueue();
    }

    /**
     * Converts a bucket to a user-readable string.
     *
     * @return string
     */
    public function __toString()
    {
        return 'BUCKET '.$this->name;
    }
}
