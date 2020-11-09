<?php

namespace Discord\Http;

use React\Promise\ExtendedPromiseInterface;

interface DriverInterface
{
    /**
     * Runs a request.
     *
     * @param string $method
     * @param string $url
     * @param string $content
     * @param array $headers
     *
     * @return ExtendedPromiseInterface
     */
    public function runRequest(string $method, string $url, string $content, array $headers): ExtendedPromiseInterface;
}
