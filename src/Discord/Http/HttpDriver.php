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

use Discord\Http\Http;
use Discord\Parts\Channel\Channel;

/**
 * Interface for HTTP drivers.
 *
 * @author David Cole <david@team-reflex.com>
 */
interface HttpDriver
{
    /**
     * Runs an HTTP request.
     *
     * @param string $method  The HTTP method to use.
     * @param string $url     The endpoint that will be queried.
     * @param array  $headers The headers to send in the request.
     * @param string $body    The request content.
     * @param array  $options An array of Guzzle options.
     *
     * @return \React\Promise\Promise
     */
    public function runRequest($method, $url, $headers, $body, array $options = []);

    /**
     * Sends a file to a channel.
     *
     * @param Http    $http     The HTTP client.
     * @param Channel $channel  The channel to send the file to.
     * @param string  $filepath The path to the file.
     * @param string  $filename The name of the file when it is uploaded.
     * @param string  $content  The content to send with the message.
     * @param bool    $tts      Whether to send the message as TTS.
     * @param string  $token    The client token.
     *
     * @return \React\Promise\Promise
     */
    public function sendFile(Http $http, Channel $channel, $filepath, $filename, $content, $tts, $token);

    /**
     * Runs a blocking HTTP request.
     *
     * @param string $method  The HTTP method to use.
     * @param string $url     The endpoint that will be queried.
     * @param array  $headers The headers to send in the request.
     * @param string $body    The request content.
     *
     * @return object The request response.
     */
    public function blocking($method, $url, $headers, $body);
}
