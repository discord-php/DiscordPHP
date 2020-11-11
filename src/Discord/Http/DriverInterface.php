<?php

namespace Discord\Http;

use Psr\Http\Message\ResponseInterface;
use React\Promise\ExtendedPromiseInterface;

/**
 * Interface for an HTTP driver.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
interface DriverInterface
{
    /**
     * Runs a request.
     *
     * Returns a promise resolved with a PSR response interface.
     *
     * @param Request $request
     *
     * @return ExtendedPromiseInterface<ResponseInterface>
     */
    public function runRequest(Request $request): ExtendedPromiseInterface;
}
