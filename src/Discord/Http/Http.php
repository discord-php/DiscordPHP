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

use Discord\Discord;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\InvalidTokenException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Discord\Helpers\Deferred;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;
use Throwable;

use function Discord\contains;

/**
 * Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class Http
{
    /**
     * Discord API base URL.
     *
     * @var string
     */
    const BASE_URL = 'https://discord.com/api/v'.Discord::HTTP_API_VERSION;

    /**
     * Authentication token.
     *
     * @var string
     */
    private $token;

    /**
     * Logger for HTTP requests.
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * HTTP driver.
     *
     * @var DriverInterface
     */
    protected $driver;

    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * Array of request buckets.
     *
     * @var Bucket[]
     */
    protected $buckets = [];

    /**
     * The current rate-limit.
     *
     * @var RateLimit
     */
    protected $rateLimit;

    /**
     * Timer that resets the current global rate-limit.
     *
     * @var TimerInterface
     */
    protected $rateLimitReset;

    /**
     * Http wrapper constructor.
     *
     * @param string               $token
     * @param LoopInterface        $loop
     * @param DriverInterface|null $driver
     */
    public function __construct(string $token, LoopInterface $loop, LoggerInterface $logger, DriverInterface $driver = null)
    {
        $this->token = $token;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->driver = $driver;
    }

    /**
     * Sets the driver of the HTTP client.
     *
     * @param DriverInterface $driver
     */
    public function setDriver(DriverInterface $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Runs a GET request.
     *
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function get(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->queueRequest('get', $url, $content, $headers);
    }

    /**
     * Runs a POST request.
     *
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function post(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->queueRequest('post', $url, $content, $headers);
    }

    /**
     * Runs a PUT request.
     *
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function put(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->queueRequest('put', $url, $content, $headers);
    }

    /**
     * Runs a PATCH request.
     *
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function patch(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->queueRequest('patch', $url, $content, $headers);
    }

    /**
     * Runs a DELETE request.
     *
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function delete(string $url, $content = null, array $headers = []): ExtendedPromiseInterface
    {
        return $this->queueRequest('delete', $url, $content, $headers);
    }

    /**
     * Builds and queues a request.
     *
     * @param string $method
     * @param string $url
     * @param mixed  $content
     * @param array  $headers
     *
     * @return ExtendedPromiseInterface
     */
    protected function queueRequest(string $method, string $url, $content, array $headers = []): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if (is_null($this->driver)) {
            $deferred->reject(new \Exception('HTTP driver is missing.'));

            return $deferred->promise();
        }

        $headers = array_merge($headers, [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => $this->token,
            'X-Ratelimit-Precision' => 'millisecond',
        ]);

        $baseHeaders = [
            'User-Agent' => $this->getUserAgent(),
            'Authorization' => $this->token,
            'X-Ratelimit-Precision' => 'millisecond',
        ];

        // If there is content and Content-Type is not set,
        // assume it is JSON.
        if (! is_null($content) && ! isset($headers['Content-Type'])) {
            $content = json_encode($content);

            $baseHeaders['Content-Type'] = 'application/json';
            $baseHeaders['Content-Length'] = strlen($content);
        }

        $headers = array_merge($baseHeaders, $headers);

        $fullUrl = self::BASE_URL.'/'.$url;

        $request = new Request($deferred, $method, $fullUrl, $content ?? '', $headers);
        $this->sortIntoBucket($request);

        $this->logger->debug($request.' queued');

        return $deferred->promise();
    }

    /**
     * Executes a request.
     *
     * @param Request $request
     *
     * @return ExtendedPromiseInterface
     */
    protected function executeRequest(Request $request): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($this->rateLimit) {
            $deferred->reject($this->rateLimit);

            return $deferred->promise();
        }

        $this->driver->runRequest($request)->done(function (ResponseInterface $response) use ($request, $deferred) {
            $data = json_decode((string) $response->getBody());
            $statusCode = $response->getStatusCode();

            // Discord Rate-limit
            if ($statusCode == 429) {
                $rateLimit = new RateLimit($data->global, $data->retry_after);
                $this->logger->warning($request.' hit rate-limit: '.$rateLimit);

                if ($rateLimit->isGlobal() && ! $this->rateLimit) {
                    $this->rateLimit = $rateLimit;
                    $this->rateLimitReset = $this->loop->addTimer($rateLimit->getRetryAfter() / 1000, function () {
                        $this->rateLimit = null;
                        $this->rateLimitReset = null;
                        $this->logger->info('global rate-limit reset');

                        // Loop through all buckets and check for requests
                        foreach ($this->buckets as $bucket) {
                            $bucket->checkQueue();
                        }
                    });
                }

                $deferred->reject($rateLimit->isGlobal() ? $this->rateLimit : $rateLimit);
            }
            // Bad Gateway
            // Cloudflare SSL Handshake error
            // Push to the back of the bucket to be retried.
            elseif ($statusCode == 502 || $statusCode == 525) {
                $this->logger->warning($request.' 502/525 - sorting to back of bucket');

                $this->sortIntoBucket($request);
            }
            // Any other unsuccessful status codes
            elseif ($statusCode < 200 || $statusCode >= 300) {
                $error = $this->handleError($response);
                $this->logger->warning($request.' failed: '.$error);

                $request->getDeferred()->reject($error);
            }
            // All is well
            else {
                $this->logger->debug($request.' successful');

                $deferred->resolve($response);
                $request->getDeferred()->resolve($data);
            }
        }, function (Exception $e) use ($request) {
            $this->logger->warning($request.' failed: '.$e->getMessage());

            $request->getDeferred()->reject($e);
        });

        return $deferred->promise();
    }

    /**
     * Sorts a request into a bucket.
     *
     * @param Request $request
     */
    protected function sortIntoBucket(Request $request): void
    {
        $bucket = $this->getBucket($request->getBucketID());
        $bucket->enqueue($request);
    }

    /**
     * Gets a bucket.
     *
     * @param string $key
     *
     * @return Bucket
     */
    protected function getBucket(string $key): Bucket
    {
        if (! isset($this->buckets[$key])) {
            $bucket = new Bucket($key, $this->loop, $this->logger, function (Request $request) {
                return $this->executeRequest($request);
            });

            $this->buckets[$key] = $bucket;
        }

        return $this->buckets[$key];
    }

    /**
     * Returns an exception based on the request.
     *
     * @param ResponseInterface $response
     *
     * @return Throwable
     */
    public function handleError(ResponseInterface $response): Throwable
    {
        switch ($response->getStatusCode()) {
            case 400:
                return new DiscordRequestFailedException($response->getReasonPhrase());
            case 401:
                return new InvalidTokenException($response->getReasonPhrase());
            case 403:
                return new NoPermissionsException($response->getReasonPhrase());
            case 404:
                return new NotFoundException($response->getReasonPhrase());
            case 500:
                if (contains(strtolower((string) $response->getBody()), ['longer than 2000 characters', 'string value is too long'])) {
                    // Response was longer than 2000 characters and was blocked by Discord.
                    return new ContentTooLongException('Response was more than 2000 characters. Use another method to get this data.');
                }
            default:
            return new DiscordRequestFailedException($response->getReasonPhrase());
        }
    }

    /**
     * Returns the User-Agent of the HTTP client.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        return 'DiscordBot (https://github.com/discord-php/DiscordPHP, '.Discord::VERSION.')';
    }
}
