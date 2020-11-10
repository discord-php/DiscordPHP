<?php

namespace Discord\Http;

use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
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
     * Callback for when a request is ready.
     *
     * @var callable
     */
    protected $runRequest;

    /**
     * Whether we are checking the queue.
     *
     * @var boolean
     */
    protected $checkerRunning = false;

    /**
     * Bucket constructor.
     *
     * @param string $name
     * @param callable $runRequest
     */
    public function __construct(string $name, LoopInterface $loop, callable $runRequest)
    {
        $this->queue = new SplQueue;
        $this->name = $name;
        $this->loop = $loop;
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
            // Queue is empty, job done.
            if ($this->queue->isEmpty()) {
                $this->checkerRunning = false;
                return;
            }

            $request = $this->queue->dequeue();

            ($this->runRequest)($request)->done(function (ResponseInterface $response) use (&$checkQueue) {
                // TODO Handle rate-limit headers

                // Check for more requests
                $checkQueue();
            }, function (RateLimit $rateLimit) use (&$checkQueue, $request) {
                // Handle meeting rate-limit(s)

                // Bucket-specific rate-limit
                // Re-queue the request and wait the retry after time
                if (! $rateLimit->isGlobal()) {
                    $this->queue->enqueue($request);
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
}
