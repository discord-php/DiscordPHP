<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use React\Promise\Deferred as ReactDeferred;
use React\Promise\ExtendedPromiseInterface;

/**
 * Wrapper for extended promisor interface. Work-around until react/promise v3.0.
 */
class Deferred extends ReactDeferred implements ExtendedPromisorInterface
{
    public function promise(): ExtendedPromiseInterface
    {
        return parent::promise();
    }
}
