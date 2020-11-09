<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

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
