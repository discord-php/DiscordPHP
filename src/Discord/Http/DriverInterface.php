<?php

namespace Discord\Http;

use React\Promise\ExtendedPromiseInterface;

interface DriverInterface
{
    /**
     * Runs a request.
     *
     * @param Request $request
     *
     * @return ExtendedPromiseInterface
     */
    public function runRequest(Request $request): ExtendedPromiseInterface;
}
