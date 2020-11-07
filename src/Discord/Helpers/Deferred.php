<?php

namespace Discord\Helpers;

use React\Promise\Deferred as ReactDeferred;
use React\Promise\ExtendedPromiseInterface;

/**
 * Wrapper for extended promisor interface. Work-around until react/promise v3.0
 */
class Deferred extends ReactDeferred implements ExtendedPromisorInterface
{
    public function promise(): ExtendedPromiseInterface
    {
        return parent::promise();
    }
}
