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
     * @param string $method The HTTP method to use.
     * @param string $url The endpoint that will be queried.
     * @param array $headers The headers to send in the request.
     * @param string $body The request content.
     *
     * @return \React\Promise\Promise
     */
    public function runRequest($method, $url, $headers, $body);

    /**
     * Sends a file to a channel.
     *
     * @param Channel $channel  The channel to send the file to.
     * @param string  $filepath The path to the file.
     * @param string  $filename The name of the file when it is uploaded.
     *
     * @return \React\Promise\Promise
     */
    public function sendFile(Channel $channel, $filepath, $filename);

    /**
     * Runs a blocking HTTP request.
     *
     * @param string $method The HTTP method to use.
     * @param string $url The endpoint that will be queried.
     * @param array $headers The headers to send in the request.
     * @param string $body The request content.
     *
     * @return object The request response.
     */
    public function blocking($method, $url, $headers, $body);
}
