<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Discord\Parts\Channel\Channel;
use Discord\Wrapper\CacheWrapper;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;

/**
 * Provides an easy wrapper for the Guzzle HTTP client.
 */
class Guzzle extends GuzzleClient
{
    /**
     * The Base URL of the API.
     *
     * @var string
     */
    const BASE_URL = 'https://discordapp.com/api';

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
     * Guzzle constructor.
     *
     * @param CacheWrapper $cache
     * @param string       $token
     * @param string       $version
     */
    public function __construct(CacheWrapper $cache, $token, $version)
    {
        $this->cache   = $cache;
        $this->token   = $token;
        $this->version = $version;

        return parent::__construct(['http_errors' => false, 'allow_redirects' => true]);
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $name   The endpoint that will be queried.
     * @param array  $params Parameters that will be encoded into JSON and sent with the request.
     *
     * @return object An object that was returned from the Discord servers.
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

        return $this->runRequest(strtolower($name), $url, $content, !$noAuth, $headers, $cache);
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
     * @throws ContentTooLongException
     * @throws DiscordRequestFailedException
     * @throws NoPermissionsException
     * @throws NotFoundException
     *
     * @return object An object that was returned from the Discord servers.
     */
    private function runRequest($method, $url, $content, $auth, $extraHeaders, $cache)
    {
        $queryUrl = static::BASE_URL.'/'.$url;

        $key = 'guzzle.'.sha1($queryUrl);
        if ($method === 'get' && $this->cache->has($key)) {
            return $this->cache->get($key);
        }

        $headers = [
            'User-Agent'   => $this->getUserAgent(),
            'Content-Type' => 'application/json',
        ];

        if ($auth) {
            $headers['authorization'] = 'Bot '.$this->token;
        }

        $headers = array_merge($headers, $extraHeaders);

        $done     = false;
        $finalRes = null;
        $content  = (is_null($content)) ? null : json_encode($content);
        $count    = 0;

        while (!$done) {
            $request  = new Request($method, $queryUrl, $headers, $content);
            $response = $this->send($request);

            // Bad Gateway
            // Cloudflare SSL Handshake
            if ($response->getStatusCode() === 502 || $response->getStatusCode() === 525) {
                if ($count > 3) {
                    $this->handleError(
                        $response->getStatusCode(),
                        $response->getReasonPhrase(),
                        $response->getBody(true),
                        $url
                    );
                    continue;
                }

                $count++;
                continue;
            }

            // Rate limiting
            if ($response->getStatusCode() === 429) {
                $tts = (int) $response->getHeader('Retry-After')[0] * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                $this->handleError(
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                    $response->getBody(true),
                    $url
                );
                continue;
            }

            $done     = true;
            $finalRes = $response;
        }

        $json = json_decode($finalRes->getBody());

        if ($method === 'get' && $cache !== false) {
            $this->cache->set($key, $json, $cache === null ? null : (int) $cache);
        }

        return $json;
    }

    /**
     * Handles an error code.
     *
     * @param int             $errorCode The HTTP status code.
     * @param string|Response $message   The HTTP reason phrase.
     * @param string|null     $content   The HTTP response content.
     * @param string|null     $url       The HTTP url.
     *
     * @throws \Discord\Exceptions\DiscordRequestFailedException Thrown when the request fails.
     * @throws \Discord\Exceptions\Rest\ContentTooLongException  Thrown when the content is longer than 2000
     *                                                           characters.
     * @throws \Discord\Exceptions\Rest\NotFoundException        Thrown when the server returns 404 Not Found.
     * @throws \Discord\Exceptions\Rest\NoPermissionsException   Thrown when you do not have permissions to do
     *                                                           something.
     */
    public function handleError($errorCode, $message, $content = null, $url = null)
    {
        if (!is_string($message)) {
            $message = $message->getReasonPhrase();
        }
        $message .= " - {$content} - {$url}";

        switch ($errorCode) {
            case 400:
                throw new DiscordRequestFailedException("Error code 400: We sent a bad request. {$message}");
                break;
            case 403:
                throw new NoPermissionsException("Error code 403: You do not have permission to do this. {$message}");
                break;
            case 404:
                throw new NotFoundException("Error code 404: This resource does not exist. {$message}");
                break;
            case 500:
                if (Str::contains(strtolower($content), ['longer than 2000 characters', 'string value is too long'])) {
                    // Discord has set a restriction with content sent over REST,
                    // if it is more than 2000 characters long it will not be
                    // sent and will return a 500 error.
                    //
                    // There is no way around this, you must use WebSockets.
                    throw new ContentTooLongException(
                        'The expected content was more than 2000 characters. Use websockets if you need this content.'
                    );
                }

                throw new DiscordRequestFailedException(
                    "Error code 500: This usually means something went wrong with Discord. {$message}"
                );
                break;
            default:
                throw new DiscordRequestFailedException(
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

    public function sendFile(Channel $channel, $filepath, $filename)
    {
        $url = static::BASE_URL."/channels/{$channel->id}/messages";

        $headers = [
            'User-Agent'    => $this->getUserAgent(),
            'authorization' => $this->token,
        ];

        $done     = false;
        $finalRes = null;

        while (!$done) {
            $response = $this->request(
                'post',
                $url,
                [
                    'headers'   => $headers,
                    'multipart' => [
                        [
                            'name'     => 'file',
                            'contents' => fopen($filepath, 'r'),
                            'filename' => $filename,
                        ],
                    ],
                ]
            );

            // Rate limiting
            if ($response->getStatusCode() === 429) {
                $tts = $response->getHeader('Retry-After') * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                $this->handleError($response->getStatusCode(), $response->getReasonPhrase());
                continue;
            }

            $done     = true;
            $finalRes = $response;
        }

        return json_decode($finalRes->getBody());
    }
}
