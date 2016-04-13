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

use Discord\Discord;
use Discord\Http\RateLimit\GlobalBucket;
use Discord\Http\RateLimit\ServerBucket;
use Discord\Parts\Channel\Channel;
use Discord\Wrapper\CacheWrapper;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

/**
 * GuzzleHTTP driver.
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
     * Rate limit buckets.
     *
     * @var array Buckets.
     */
    protected $buckets = [];

    /**
     * Constructs a Guzzle driver.
     *
     * @param CacheWrapper       $cache The cache wrapper.
     * @param LoopInterface|null $loop  The ReactPHP event loop.
     *
     * @return void
     */
    public function __construct(CacheWrapper $cache, LoopInterface $loop = null)
    {
        $this->cache = $cache;
        $options     = ['http_errors' => false, 'allow_redirects' => true];

        if (! is_null($loop)) {
            $this->async        = true;
            $this->loop         = $loop;
            $this->adapter      = new HttpClientAdapter($this->loop);
            $options['handler'] = HandlerStack::create($this->adapter);

            $this->buckets = [
                'global' => new GlobalBucket($loop),
            ];
        }

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
            Http::BASE_URL.'/'.$url,
            $headers,
            $body
        );
        $count = 0;

        $sendRequest = function () use (&$sendRequest, &$count, $request, $deferred, $options) {
            $promise = $this->sendAsync($request, $options);

            $promise->then(function ($response) use (&$count, &$sendRequest, $deferred) {
                // Discord Rate-Limiting
                if ($response->getStatusCode() == 429) {
                    $tts = (int) $response->getHeader('Retry-After')[0] / 1000;

                    switch ($this->async) {
                        case true:
                            $this->loop->addTimer($tts, $sendRequest);
                            $deferred->notify('You have been rate limited.');
                            break;
                        default:
                            $deferred->notify('You have been rate limited.');
                            usleep($tts * 1000 * 1000);
                            $sendRequest();
                            break;
                    }
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

                    if ($this->async) {
                        $this->loop->addTimer(0.1, $sendRequest);
                    }
                    // It's more harm to us for sleeping for 0.1 and blocking than sending the request again.
                    else {
                        $sendRequest();
                    }
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

            if (! $this->async) {
                $promise->wait();
            }
        };

        if ($this->async) {
            $this->buckets['global']->queue()->then(function () use ($sendRequest, $url, $deferred) {
                if (preg_match('/channels\/([0-9]+)\/messages/', $url, $matches)) {
                    $channel = $this->cache->get('channel.'.$matches[1]);
                    $guild = $this->cache->get('guild.'.$channel->guild_id);

                    if (is_null($guild)) {
                        $sendRequest();

                        return;
                    }

                    if (! isset(
                        $this->buckets['guild.'.$guild->id]
                    )) {
                        $this->buckets['guild.'.$guild->id] = new ServerBucket($this->loop, $guild);
                    }

                    $this->buckets['guild.'.$guild->id]->queue()->then($sendRequest, null, function ($content) use ($deferred) {
                        $deferred->notify($content);
                    });
                }
            }, null, function ($content) use ($deferred) {
                $deferred->notify($content);
            });
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
