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

use React\Promise\ExtendedPromiseInterface;

/**
 * Expands on the react/promise PromisorInterface by returning an extended
 * promise.
 *
 * @since 5.0.12
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
