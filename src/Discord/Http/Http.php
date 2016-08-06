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

use Discord\Cache\Cache;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\Wrapper\CacheWrapper;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use React\Promise\Deferred;

/**
 * Provides an easy wrapper for HTTP requests, allows for interchangable connectors.
 */
class Http
{
    /**
     * The Base URL of the API.
     *
     * @var string
     */
    const BASE_URL = 'https://discordapp.com/api';

    /**
     * The length of time requests will be cached for.
     *
     * @var int Length of time to cache requests.
     */
    const CACHE_TTL = 300;

    /**
     * @var CacheWrapper
     */
    private $cache;

    /**
     * @var string
     */
    private $token;

    /**
     * @var
     */
    private $version;

    /**
     * The request driver.
     *
     * @var HttpDriver
     */
    protected $driver;

    /**
     * Guzzle constructor.
     *
     * @param CacheWrapper $cache
     * @param string       $token
     * @param string       $version
     * @param HttpDriver   $driver  The request driver.
     */
    public function __construct(CacheWrapper $cache, $token, $version, $driver = null)
    {
        if (is_null($driver)) {
            $driver = new Guzzle($cache);
        }

        $this->cache   = $cache;
        $this->token   = $token;
        $this->version = $version;
        $this->driver  = $driver;
    }

    /**
     * Sets the HTTP driver.
     *
     * @param HttpDriver $driver
     *
     * @return void
     */
    public function setDriver(HttpDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $name   The endpoint that will be queried.
     * @param array  $params Parameters that will be encoded into JSON and sent with the request.
     *
     * @return \React\Promise\Promise
     *
     * @see \Discord\Helpers\Guzzle::runRequest() This function will be forwareded onto runRequest.
     */
    public function __call($name, $params)
    {
        $url      = $params[0];
        $content  = (isset($params[1])) ? $params[1] : null;
        $headers  = (isset($params[2])) ? $params[2] : [];
        $cache    = (isset($params[3])) ? $params[3] : null;
        $blocking = (isset($params[4])) ? $params[4] : false;
        $options  = (isset($params[5])) ? $params[5] : [];

        return $this->runRequest(strtolower($name), $url, $content, $headers, $cache, $blocking, $options);
    }

    /**
     * Runs http calls.
     *
     * @param string        $method       The request method.
     * @param string        $url          The endpoint that will be queried.
     * @param array         $content      Parameters that will be encoded into JSON and sent with the request.
     * @param array         $extraHeaders Extra headers to send with the request.
     * @param bool|int|null $cache        If an integer is passed, used as cache TTL, if null is passed, default TTL is
     *                                    used, if false, cache is disabled
     * @param bool          $blocking     Whether the request should be sent as blocking.
     * @param array         $options      Array of options to pass to Guzzle.
     *
     * @throws ContentTooLongException
     * @throws DiscordRequestFailedException
     * @throws NoPermissionsException
     * @throws NotFoundException
     *
     * @return \React\Promise\Promise
     */
    private function runRequest($method, $url, $content, $extraHeaders, $cache, $blocking, $options)
    {
        $deferred = new Deferred();

        $key = 'guzzle.'.sha1($url);
        if ($method === 'get' && $this->cache->has($key)) {
            $deferred->resolve($this->cache->get($key));

            return $deferred->promise();
        }

        $headers = [
            'User-Agent'     => $this->getUserAgent(),
        ];

        $headers['authorization'] = 'Bot '.$this->token;

        $headers = array_merge($headers, $extraHeaders);

        if (! is_null($content)) {
            $headers['Content-Type']   = 'application/json';
            $content                   = json_encode($content);
            $headers['Content-Length'] = strlen($content);
        }

        if ($blocking) {
            $response = $this->driver->blocking($method, $url, $headers, $content);

            return json_decode($response->getBody());
        }

        $this->driver->runRequest($method, $url, $headers, $content, $options)->then(
            function ($response) use ($method, $cache, $key, $deferred) {
                $json = json_decode($response->getBody());

                if ($method === 'get' && $cache !== false) {
                    $this->cache->set($key, $json, $cache === null ? static::CACHE_TTL : (int) $cache);
                }

                $deferred->resolve($json);
            },
            function ($e) use ($deferred, $url) {
                if (! ($e instanceof \Throwable)) {
                    $e = $this->handleError(
                        $e->getStatusCode(),
                        $e->getReasonPhrase(),
                        $e->getBody(),
                        $url
                    );
                }

                $deferred->reject($e);
            },
            function ($content) use ($deferred) {
                $deferred->notify($content);
            }
        );

        return $deferred->promise();
    }

    /**
     * Uploads a file to a channel.
     *
     * @param Channel $channel  The channel to send to.
     * @param string  $filepath The path to the file.
     * @param string  $filename The name to upload the file as.
     * @param string  $content  Extra text content to go with the file.
     * @param bool    $tts      Whether the message should be TTS.
     *
     * @return \React\Promise\Promise
     */
    public function sendFile(Channel $channel, $filepath, $filename, $content, $tts)
    {
        $multipart = [
            [
                'name'     => 'file',
                'contents' => fopen($filepath, 'r'),
                'filename' => $filename,
            ],
            [
                'name'     => 'tts',
                'contents' => ($tts ? 'true' : 'false'),
            ],
            [
                'name'     => 'content',
                'contents' => (string) $content,
            ],
        ];

        return $this->runRequest(
            'POST',
            "channels/{$channel->id}/messages",
            null,
            [],
            false,
            false,
            ['multipart' => $multipart]
        );
    }

    /**
     * Handles an error code.
     *
     * @param int             $errorCode The HTTP status code.
     * @param string|Response $message   The HTTP reason phrase.
     * @param string          $content   The HTTP response content.
     * @param string          $url       The HTTP url.
     *
     * @return \Discord\Exceptions\DiscordRequestFailedException Returned when the request fails.
     * @return \Discord\Exceptions\Rest\ContentTooLongException  Returned when the content is longer than 2000
     *                                                           characters.
     * @return \Discord\Exceptions\Rest\NotFoundException        Returned when the server returns 404 Not Found.
     * @return \Discord\Exceptions\Rest\NoPermissionsException   Returned when you do not have permissions to do
     *                                                           something.
     */
    public function handleError($errorCode, $message, $content, $url)
    {
        if (! is_string($message)) {
            $message = $message->getReasonPhrase();
        }

        $message .= " - {$content} - {$url}";

        switch ($errorCode) {
            case 400:
                return new DiscordRequestFailedException("Error code 400: We sent a bad request. {$message}");
                break;
            case 403:
                return new NoPermissionsException("Error code 403: You do not have permission to do this. {$message}");
                break;
            case 404:
                return new NotFoundException("Error code 404: This resource does not exist. {$message}");
                break;
            case 500:
                if (Str::contains(strtolower($content), ['longer than 2000 characters', 'string value is too long'])) {
                    // Discord has set a restriction with content sent over REST,
                    // if it is more than 2000 characters long it will not be
                    // sent and will return a 500 error.
                    //
                    // There is no way around this, you must use WebSockets.
                    return new ContentTooLongException(
                        'The expected content was more than 2000 characters. Use websockets if you need this content.'
                    );
                }

                return new DiscordRequestFailedException(
                    "Error code 500: This usually means something went wrong with Discord. {$message}"
                );
                break;
            default:
                return new DiscordRequestFailedException(
                    "Error code {$errorCode}: There was an error processing the request. {$message}"
                );
                break;
        }
    }

    /**
     * Returns the User-Agent of the API.
     *
     * @return string
     */
    public function getUserAgent()
    {
        return 'DiscordPHP/'.$this->version.' DiscordBot (https://github.com/teamreflex/DiscordPHP, '.$this->version.')';
    }
}
