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

use Discord\Exceptions\DiscordRequestFailedException;
use Discord\Exceptions\Rest\ContentTooLongException;
use Discord\Exceptions\Rest\NoPermissionsException;
use Discord\Exceptions\Rest\NotFoundException;
use Discord\Parts\Channel\Channel;
use Exception;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Str;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

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
    const BASE_URL = 'https://discord.com/api';

    /**
     * The length of time requests will be cached for.
     *
     * @var int Length of time to cache requests.
     */
    const CACHE_TTL = 300;

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
     * @param string     $token
     * @param string     $version
     * @param HttpDriver $driver  The request driver.
     */
    public function __construct(string $token, string $version, HttpDriver $driver)
    {
        $this->token = $token;
        $this->version = $version;
        $this->driver = $driver;
    }

    /**
     * Sets the HTTP driver.
     *
     * @param HttpDriver $driver
     */
    public function setDriver(HttpDriver $driver): void
    {
        $this->driver = $driver;
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $name   The endpoint that will be queried.
     * @param array  $params Parameters that will be encoded into JSON and sent with the request.
     *
     * @return PromiseInterface
     *
     * @see \Discord\Helpers\Guzzle::runRequest() This function will be forwareded onto runRequest.
     */
    public function __call(string $name, array $params): PromiseInterface
    {
        $url = $params[0];
        $content = (isset($params[1])) ? $params[1] : null;
        $headers = (isset($params[2])) ? $params[2] : [];
        $cache = (isset($params[3])) ? $params[3] : null;
        $options = (isset($params[5])) ? $params[5] : [];

        return $this->runRequest(strtolower($name), $url, $content, $headers, $cache, $options);
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
     * @param array         $options      Array of options to pass to Guzzle.
     *
     * @throws ContentTooLongException
     * @throws DiscordRequestFailedException
     * @throws NoPermissionsException
     * @throws NotFoundException
     *
     * @return PromiseInterface
     */
    private function runRequest(string $method, string $url, ?array $content, ?array $extraHeaders, $cache, ?array $options): PromiseInterface
    {
        $deferred = new Deferred();
        $disable_json = false;

        $headers = [
            'User-Agent' => $this->getUserAgent(),
        ];

        if (! isset($options['multipart'])) {
            $headers['Content-Length'] = 0;
        }

        $headers['authorization'] = $this->token;

        $headers = array_merge($headers, $extraHeaders);

        if (! is_null($content)) {
            $headers['Content-Type'] = 'application/json';
            $content = json_encode($content);
            $headers['Content-Length'] = strlen($content);
        }

        if (array_key_exists('disable_json', $options)) {
            $disable_json = $options['disable_json'];
            unset($options['disable_json']);
        }

        $this->driver->runRequest($method, $url, $headers, $content, $options)->then(
            function ($response) use ($method, $cache, $deferred, $disable_json) {
                if ($disable_json) {
                    return $deferred->resolve($response->getBody());
                }

                $json = json_decode($response->getBody());

                $deferred->resolve($json);
            },
            function ($e) use ($deferred, $url) {
                if (! ($e instanceof \Exception)) {
                    if (is_callable([$e, 'getStatusCode'])) {
                        $e = $this->handleError(
                            $e->getStatusCode(),
                            $e->getReasonPhrase(),
                            $e->getBody(),
                            $url
                        );
                    } else {
                        $e = $this->handleError(
                            0,
                            'unknown',
                            'unknown',
                            $url
                        );
                    }
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
     * @return PromiseInterface
     */
    public function sendFile(Channel $channel, string $filepath, ?string $filename, ?string $content, ?bool $tts): PromiseInterface
    {
        $deferred = new Deferred();

        $boundary = '----DiscordPHPSendFileBoundary';
        $body = '';
        $multipart = [
            [
                'name' => 'file',
                'contents' => file_get_contents($filepath),
                'filename' => $filename,
            ],
            [
                'name' => 'tts',
                'contents' => ($tts ? 'true' : 'false'),
            ],
            [
                'name' => 'content',
                'contents' => (string) $content,
            ],
        ];

        $body = $this->arrayToMultipart($multipart, $boundary);
        $headers = [
            'Content-Type' => 'multipart/form-data; boundary='.substr($boundary, 2),
            'Content-Length' => strlen($body),
            'authorization' => $this->token,
            'User-Agent' => $this->getUserAgent(),
        ];

        $this->driver->runRequest(
            'POST',
            "channels/{$channel->id}/messages",
            $headers,
            $body
        )->then(
            function ($response) use ($deferred) {
                $json = json_decode($response->getBody());
                $deferred->resolve($json);
            },
            function ($e) use ($deferred, $channel) {
                if (! ($e instanceof \Exception)) {
                    if (is_callable([$e, 'getStatusCode'])) {
                        $e = $this->handleError(
                            $e->getStatusCode(),
                            $e->getReasonPhrase(),
                            $e->getBody(),
                            "channels/{$channel->id}/messages"
                        );
                    } else {
                        $e = $this->handleError(
                            0,
                            'unknown',
                            'unknown',
                            "channels/{$channel->id}/messages"
                        );
                    }
                }

                $deferred->reject($e);
            }
        );

        return $deferred->promise();
    }

    /**
     * Converts an array of key => value to a multipart body.
     *
     * @param array  $multipart
     * @param string $boundary
     *
     * @return string
     */
    private function arrayToMultipart(array $multipart, string $boundary): string
    {
        $body = '';

        foreach ($multipart as $part) {
            $body .= $boundary."\n";
            $body .= 'Content-Disposition: form-data; name="'.$part['name'].'"';

            if (isset($part['filename'])) {
                $body .= '; filename="'.$part['filename'].'"';
            }

            $body .= "\n";

            if (isset($part['headers'])) {
                foreach ($part['headers'] as $header => $val) {
                    $body .= $header.': '.$val."\n";
                }
            }

            $body .= "\n".$part['contents']."\n";
        }

        $body .= $boundary."--\n";

        return $body;
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
    public function handleError(int $errorCode, $message, string $content, string $url): Exception
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
    public function getUserAgent(): string
    {
        return 'DiscordBot (https://github.com/teamreflex/DiscordPHP, '.$this->version.')';
    }
}
