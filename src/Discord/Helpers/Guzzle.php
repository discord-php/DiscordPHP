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

use Discord\Discord;
use Discord\Exceptions\ContentTooLongException;
use Discord\Exceptions\DiscordRequestFailedException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Request;

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
     * Handles dynamic calls to the class.
     *
     * @param string $url The endpoint that will be queried.
     * @param array  $params Parameters that will be encoded into JSON and sent with the request.
     * @param bool   $noauth Whether the authentication token will be sent with the request.
     *
     * @return object An object that was returned from the Discord servers.
     * @see \Discord\Helpers\Guzzle::runRequest() This function will be forwareded onto runRequest.
     */
    public static function __callStatic($name, $params)
    {
        $url = $params[0];
        $content = (isset($params[1])) ? $params[1] : null;
        $auth = (isset($params[2])) ? true : false;

        return self::runRequest($name, $url, $content, $auth);
    }

    /**
     * Runs http calls.
     *
     * @param string $method The request method.
     * @param string $url The endpoint that will be queried.
     * @param array  $content Parameters that will be encoded into JSON and sent with the request.
     * @param bool   $auth Whether the authentication token will be sent with the request.
     *
     * @return object An object that was returned from the Discord servers.
     */
    public static function runRequest($method, $url, $content, $auth)
    {
        $guzzle = new GuzzleClient(['http_errors' => false, 'allow_redirects' => true]);
        $url = self::$base_url."/{$url}";

        $headers = [
            'User-Agent' => self::getUserAgent(),
            'Content-Type' => 'application/json',
        ];

        if (! $auth) {
            $headers['authorization'] = DISCORD_TOKEN;
        }

        $done = false;
        $finalRes = null;
        $content = (is_null($content)) ? null : json_encode($content);

        while (! $done) {
            $request = new Request($method, $url, $headers, $content);
            $response = $guzzle->send($request);

            // Rate limiting
            if ($response->getStatusCode() == 429) {
                $tts = (int) $response->getHeader('Retry-After')[0] * 1000;
                usleep($tts);
                continue;
            }

            // Not good!
            if ($response->getStatusCode() < 200 || $response->getStatusCode() > 226) {
                self::handleError($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody(true));
                continue;
            }

            $done = true;
            $finalRes = $response;
        }

        return json_decode($finalRes->getBody());
    }

    /**
     * Handles an error code.
     *
     * @param int    $error_code The HTTP status code.
     * @param string $message The HTTP reason phrase.
     * @param string $content The HTTP response content.
     *
     * @throws \Discord\Exceptions\DiscordRequestFailedException Thrown when the request fails.
     * @throws \Discord\Exceptions\ContentTooLongException Thrown when the content is longer than 2000 characters.
     */
    public static function handleError($error_code, $message, $content)
    {
        if (! is_string($message)) {
            $message = $message->getReasonPhrase();
        }

        $message .= " - {$content}";

        if (false !== strpos($content, 'longer than 2000 characters') && $error_code == 500) {
            // Discord has set a restriction with content sent over REST,
            // if it is more than 2000 characters long it will not be
            // sent and will return a 500 error.
            //
            // There is no way around this, you must use WebSockets.
            throw new ContentTooLongException('The expected content was more than 2000 characters. Use websockets if you need this content.');
        }

        switch ($error_code) {
            case 400:
                $response = "Error code 400: This usually means you have entered an incorrect Email or Password. {$message}";
                break;
            case 500:
                $response = "Error code 500: This usually means something went wrong with Discord. {$message}";
                break;
            case 403:
                $response = "Erorr code 403: You do not have permission to do this. {$message}";
                break;
            default:
                $response = "Erorr code {$error_code}: There was an error processing the request. {$message}";
                break;
        }

        throw new DiscordRequestFailedException($response);
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
