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
use Discord\Parts\Channel\Channel;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use WyriHaximus\React\GuzzlePsr7\HttpClientAdapter;

class Guzzle extends GuzzleClient implements HttpDriver
{
    /**
     * Whether we are operating as async.
     *
     * @var bool Async.
     */
    protected $async = false;

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
     * Constructs a Guzzle driver.
     *
     * @param LoopInterface|null $loop The ReactPHP event loop.
     *
     * @return void
     */
    public function __construct(LoopInterface $loop = null)
    {
        $options = ['http_errors' => false, 'allow_redirects' => true];

        if (!is_null($loop)) {
            $this->async        = true;
            $this->loop         = $loop;
            $this->adapter      = new HttpClientAdapter($this->loop);
            $options['handler'] = HandlerStack::create($this->adapter);
        }

        return parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function runRequest($method, $url, $headers, $body)
    {
        $deferred = new Deferred();

        $request = ($method instanceof Request) ? $method : new Request(
            $method,
            Http::BASE_URL.'/'.$url,
            $headers,
            $body
        );
        $count = 0;

        $sendRequest = function () use (&$sendRequest, &$count, $request, $deferred) {
            $promise = $this->sendAsync($request);

            $promise->then(function ($response) use (&$count, $deferred) {
                // Discord Rate-Limiting
                if ($response->getStatusCode() == 429) {
                    $tts = (int) $response->getHeader('Retry-After')[0] / 1000;

                    switch ($this->async) {
                        case true:
                            $this->loop->addTimer($tts, $sendRequest);
                            break;
                        default:
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

                    $sendRequest();
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

            if (!$this->async) {
                $promise->wait();
            }
        };

        $sendRequest();

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function sendFile(Channel $channel, $filepath, $filename)
    {
        $deferred = new Deferred();

        if (!file_exists($filepath)) {
            return \React\Promise\reject(new \Exception('The specified file path does not exist.'));
        }

        $data     = file_get_contents($filepath);
        $boundary = '-----------------------------735323031399963166993862150';

        $headers = [
            'User-Agent'     => 'DiscordPHP/'.Discord::VERSION.' DiscordBot (https://github.com/teamreflex/DiscordPHP, '.Discord::VERSION.')',
            'Content-Type'   => 'multipart/form-data; boundary='.$boundary,
            'Content-Length' => strlen($data),
        ];

        $request = new Request(
            'POST',
            Http::BASE_URL."/channels/{$channel->id}/messages",
            $headers,
            $data.PHP_EOL.$boundary
        );

        return $this->runRequest($request, null, null, null);
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
