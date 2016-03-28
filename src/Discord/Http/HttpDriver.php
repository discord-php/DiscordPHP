<?php

namespace Discord\Http;

use Discord\Parts\Channel\Channel;

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
}