<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Discord\Cache\Cache;
use Discord\Discord;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Str;

/**
 * Provides an easy wrapper for the Guzzle HTTP client.
 */
class Guzzle
{
    /**
     * The Base URL of the API.
     *
     * @var string
     */
    public static $base_url = 'https://discordapp.com/api';

    /**
     * The length of time requests will be cached for.
     *
     * @var int Length of time to cache requests.
     */
    public static $cacheTtl = 300;

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $url    The endpoint that will be queried.
     * @param array  $params Parameters that will be encoded into JSON and sent with the request.
     * @param bool   $noauth Whether the authentication token will be sent with the request.
     *
     * @return object An object that was returned from the Discord servers.
     *
     * @see \Discord\Helpers\Guzzle::runRequest() This function will be forwareded onto runRequest.
     */
    public static function __callStatic($name, $params)
    {
        $url     = $params[0];
        $content = (isset($params[1])) ? $params[1] : null;
        $auth    = (isset($params[2])) ? true : false;
        $headers = (isset($params[3])) ? $params[3] : [];

        return self::runRequest($name, $url, $content, $auth, $headers);
    }

    /**
     * Runs http calls.
     *
     * @param string $method       The request method.
     * @param string $url          The endpoint that will be queried.
     * @param array  $content      Parameters that will be encoded into JSON and sent with the request.
     * @param bool   $auth         Whether the authentication token will be sent with the request.
     * @param array  $extraHeaders Extra headers to send with the request.
     *
     * @return object An object that was returned from the Discord servers.
     */
    public static function runRequest($method, $url, $content, $auth, $extraHeaders)
    {
        $guzzle    = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $query_url = self::$base_url."/{$url}";

        if (Cache::has("guzzle:{$query_url}") && (strtolower($method) == 'get')) {
            return Cache::get("guzzle:{$query_url}");
        }

        $headers = [
            'User-Agent'   => self::getUserAgent(),
            'Content-Type' => 'application/json',
        ];

        if (! $auth) {
            $headers['authorization'] = DISCORD_TOKEN;
        }

        $headers = array_merge($headers, $extraHeaders);

        $done     = false;
        $finalRes = null;
        $content  = (is_null($content)) ? null : json_encode($content);
        $count    = 0;

        while (! $done) {
            $request  = new Request($method, $query_url, $headers, $content);
            $response = $guzzle->send($request);

            // Bad Gateway
            // Cloudflare SSL Handshake
            if ($response->getStatusCode() == 502 || $response->getStatusCode() == 525) {
                if ($count > 3) {
                    self::handleError($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody(true), $url);
                    continue;
                }

                ++$count;
                continue;
            }

            // Rate limiting
            if ($response->getStatusCode() == 429) {
                $tts = (int) $response->getHeader('Retry-After')[0] * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                self::handleError($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody(true), $url);
                continue;
            }

            $done     = true;
            $finalRes = $response;
        }

        $json = json_decode($finalRes->getBody());

        if (strtolower($method) == 'get') {
            Cache::set("guzzle:{$query_url}", $json, self::$cacheTtl);
        }

        return $json;
    }

    /**
     * Sets the cache TTL.
     *
     * @param int $ttl The TTL to set.
     *
     * @return void
     */
    public static function setCacheTtl($ttl)
    {
        self::$cacheTtl = $ttl;
    }

    /**
     * Handles an error code.
     *
     * @param int    $error_code The HTTP status code.
     * @param string $message    The HTTP reason phrase.
     * @param string $content    The HTTP response content.
     * @param string $url        The HTTP url.
     *
     * @throws \Discord\Exceptions\DiscordRequestFailedException Thrown when the request fails.
     * @throws \Discord\Exceptions\Rest\ContentTooLongException  Thrown when the content is longer than 2000 characters.
     * @throws \Discord\Exceptions\Rest\NotFoundException        Thrown when the server returns 404 Not Found.
     * @throws \Discord\Exceptions\Rest\NoPermissionsException   Thrown when you do not have permissions to do something.
     */
    public static function handleError($error_code, $message, $content, $url)
    {
        if (! is_string($message)) {
            $message = $message->getReasonPhrase();
        }

        $message .= " - {$content} - {$url}";

        if (Str::contains(strtolower($content), [
                'longer than 2000 characters',
                'string value is too long',
            ]) &&
            $error_code == 500
        ) {
            // Discord has set a restriction with content sent over REST,
            // if it is more than 2000 characters long it will not be
            // sent and will return a 500 error.
            //
            // There is no way around this, you must use WebSockets.
            throw new ContentTooLongException('The expected content was more than 2000 characters. Use websockets if you need this content.');
        }

        switch ($error_code) {
            case 404:
                throw new NotFoundException("Error code 404: This resource does not exist. {$message}");
                break;
            case 400:
                throw new DiscordRequestFailedException("Error code 400: We sent a bad request. {$message}");
                break;
            case 500:
                throw new DiscordRequestFailedException("Error code 500: This usually means something went wrong with Discord. {$message}");
                break;
            case 403:
                throw new NoPermissionsException("Erorr code 403: You do not have permission to do this. {$message}");
                break;
            default:
                throw new DiscordRequestFailedException("Erorr code {$error_code}: There was an error processing the request. {$message}");
                break;
        }
    }

    /**
     * Returns the User-Agent of the API.
     *
     * @return string
     */
    public static function getUserAgent()
    {
        return 'DiscordPHP/'.Discord::VERSION.' DiscordBot (https://github.com/teamreflex/DiscordPHP, '.Discord::VERSION.')';
    }
}
