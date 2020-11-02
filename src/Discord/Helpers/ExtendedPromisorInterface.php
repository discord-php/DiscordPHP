<?php

namespace Discord\Helpers;

use React\Promise\ExtendedPromiseInterface;

/**
 * Expands on the react/promise PromisorInterface
 * by returning an extended promise.
 */
interface ExtendedPromisorInterface
{
    /**
     * Returns the promise of the deferred.
     *
     * @return ExtendedPromiseInterface
     */
    public function promise(): ExtendedPromiseInterface;
}
