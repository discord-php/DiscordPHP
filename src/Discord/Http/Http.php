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
use Discord\Http\Guzzle;
use Discord\Parts\Channel\Channel;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use Psr\Cache\CacheItemPoolInterface;
use React\Promise\Deferred;

/**
 * Provides an easy wrapper for the Guzzle HTTP client.
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
     * @var CacheItemPoolInterface
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
     * @param CacheItemPoolInterface $cache
     * @param string                 $token
     * @param string                 $version
     * @param HttpDriver $driver The request driver.
     */
    public function __construct(CacheItemPoolInterface $cache, $token, $version, $driver = null)
    {
        if (is_null($driver)) {
            $driver = new Guzzle();
        }

        $this->cache   = $cache;
        $this->token   = $token;
        $this->version = $version;
        $this->driver  = $driver;
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
        $url     = $params[0];
        $content = (isset($params[1])) ? $params[1] : null;
        $noAuth  = (isset($params[2]));
        $headers = (isset($params[3])) ? $params[3] : [];
        $cache   = (isset($params[4])) ? $params[4] : null;

        return $this->runRequest($name, $url, $content, !$noAuth, $headers, $cache);
    }

    /**
     * Runs http calls.
     *
     * @param string        $method       The request method.
     * @param string        $url          The endpoint that will be queried.
     * @param array         $content      Parameters that will be encoded into JSON and sent with the request.
     * @param bool          $auth         Whether the authentication token will be sent with the request.
     * @param array         $extraHeaders Extra headers to send with the request.
     * @param bool|int|null $cache        If an integer is passed, used as cache TTL, if null is passed, default TTL is
     *                                    used, if false, cache is disabled
     *
     * @return \React\Promise\Promise
     * 
     * @throws ContentTooLongException
     * @throws DiscordRequestFailedException
     * @throws NoPermissionsException
     * @throws NotFoundException
     */
    private function runRequest($method, $url, $content, $auth, $extraHeaders, $cache)
    {
        $deferred = new Deferred();

        $key = 'guzzle.'.sha1($url);
        if ($this->cache->hasItem($key) && strtolower($method) === 'get') {
            return $this->cache->getItem($key)->get();
        }

        $headers = [
            'User-Agent'   => $this->getUserAgent(),
            'Content-Type' => 'application/json',
        ];

        if ($auth) {
            $headers['authorization'] = 'Bot ' . $this->token;
        }

        $headers = array_merge($headers, $extraHeaders);
        $content = (is_null($content)) ? null : json_encode($content);

        $this->driver->runRequest($method, $url, $headers, $content)->then(function ($response) use ($method, $cache, $key) {
            $json = json_decode($response->getBody());

            if (strtolower($method) === 'get' && $cache !== false) {
                $item = $this->cache->getItem($key);
                $item->set($json);
                $item->expiresAfter($cache === null ? static::CACHE_TTL : (int) $cache);
                $this->cache->save($item);
            }

            $deferred->resolve($json);
        }, function ($e) use ($deferred) {
            if ($e instanceof Response) {
                $e = $this->handleError(
                    $e->getStatusCode(),
                    $e->getReasonPhrase(),
                    $e->getBody(),
                    $url
                );
            }

            $deferred->reject($e);
        });

        return $deferred->promise();
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
        if (!is_string($message)) {
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
     * Sends a file to a channel.
     *
     * @param Channel $channel  The channel to send the file to.
     * @param string  $filepath The path to the file.
     * @param string  $filename The name of the file when it is uploaded.
     *
     * @return \React\Promise\Promise 
     */
    public function sendFile(Channel $channel, $filepath, $filename)
    {
        return $this->driver->sendFile($channel, $filepath, $filename);
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
