<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Http;

use Carbon\Carbon;
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Wrapper\CacheWrapper;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

/**
 * The Guzzle PHP library driver for the HTTP client.
 *
 * @author David Cole <david@team-reflex.com>
 */
class Guzzle extends GuzzleClient implements HttpDriver
{
    /**
     * Whether we are operating as async.
     *
     * @var bool Async.
     */
    protected $async = false;

    /**
     * The cache wrapper.
     *
     * @var CacheWrapper Wrapper.
     */
    protected $cache;

    /**
     * The ReactPHP event loop.
     *
     * @var LoopInterface Event loop.
     */
    protected $loop;

    /**
     * The GuzzleHTTP -> ReactPHP connector.
     *
     * @var HttpClientAdapter The connector.
     */
    protected $adapter;

    /**
     * Whether the HTTP client has been rate limited.
     *
     * @var bool Rate limited.
     */
    protected $rateLimited = false;

    /**
     * Array of rate limit promises.
     *
     * @var array Rate Limits.
     */
    protected $rateLimits = [];

    /**
     * Constructs a Guzzle driver.
     *
     * @param CacheWrapper       $cache The cache wrapper.
     * @param LoopInterface|null $loop  The ReactPHP event loop.
     *
     * @return void
     */
    public function __construct(CacheWrapper $cache, LoopInterface $loop)
    {
        $this->cache = $cache;
        $options     = ['http_errors' => false, 'allow_redirects' => true];

        $this->async        = true;
        $this->loop         = $loop;
        $this->adapter      = new HttpClientAdapter($this->loop);
        $options['handler'] = HandlerStack::create($this->adapter);

        return parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function runRequest($method, $url, $headers, $body, array $options = [])
    {
        $deferred = new Deferred();

        $request = ($method instanceof Request) ? $method : new Request(
            $method,
            Http::BASE_URL.'/v'.Discord::HTTP_API_VERSION.'/'.$url,
            $headers,
            $body
        );
        $count = 0;

        $sendRequest = function () use (&$sendRequest, &$count, $request, $deferred, $options) {
            $promise = $this->sendAsync($request, $options);

            $promise->then(function ($response) use (&$count, &$sendRequest, $deferred) {
                if ($response->getStatusCode() !== 429 && $response->getHeader('X-RateLimit-Remaining') == 0) {
                    $this->rateLimited = true;

                    $limitEnd = Carbon::createFromTimestamp($response->getHeader('X-RateLimit-Reset'));
                    $this->loop->addTimer(Carbon::now()->diffInSeconds($limitEnd), function () {
                        foreach ($this->rateLimits as $i => $d) {
                            $d->resolve();
                            unset($this->rateLimits[$i]);
                        }

                        $this->rateLimited = false;
                    });

                    $deferred->notify('The next request will hit a rate limit.');
                }

                // Discord Rate-Limiting
                if ($response->getStatusCode() == 429) {
                    $tts = (int) $response->getHeader('Retry-After')[0] / 1000;
                    $this->rateLimited = true;

                    $deferred = new Deferred();
                    $deferred->promise()->then($sendRequest);

                    $this->rateLimits[] = $deferred;

                    $this->loop->addTimer($tts, function () {
                        foreach ($this->rateLimits as $i => $d) {
                            $d->resolve();
                            unset($this->rateLimits[$i]);
                        }

                        $this->rateLimited = false;
                    });

                    $deferred->notify('You have been rate limited.');
                }
                // Bad Gateway
                // Cloudflare SSL Handshake Error
                //
                // We just retry since this is a weird error and only happens every now and then.
                elseif ($response->getStatusCode() == 502 || $response->getStatusCode() == 525) {
                    if ($count > 3) {
                        $deferred->reject($response);

                        return;
                    }

                    // Slight delay of 0.1s to satisfy Andrei and Jake
                    $this->loop->addTimer(0.1, $sendRequest);
                }
                // Handle any other codes that are not successful.
                elseif ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                    $deferred->reject($response);
                }
                // All is good!
                else {
                    $deferred->resolve($response);
                }
            }, function ($e) use ($deferred) {
                $deferred->reject($e);
            });
        };

        if ($this->rateLimited) {
            $deferred = new Deferred();
            $deferred->promise()->then($sendRequest);
            $this->rateLimits[] = $deferred;
        } else {
            $sendRequest();
        }

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function sendFile(Http $http, Channel $channel, $filepath, $filename, $content, $tts, $token)
    {
        $multipart = [
            [
                'name'     => 'file',
                'contents' => fopen($filepath, 'r'),
                'filename' => $filename,
            ],
        ];

        if (! is_null($content)) {
            $multipart[] = [
                'name'     => 'content',
                'contents' => $content,
            ];
        }

        if ($tts) {
            $multipart[] = [
                'name'     => 'tts',
                'contents' => 'true',
            ];
        }

        return $this->runRequest('POST', "channels/{$channel->id}/messages", [
            'authorization' => $token,
            'User-Agent'    => $http->getUserAgent(),
        ], null, [
            'multipart' => $multipart,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function blocking($method, $url, $headers, $body)
    {
        $request = new Request(
            $method,
            Http::BASE_URL.'/'.$url,
            $headers,
            $body
        );

        return $this->send($request);
    }
}
