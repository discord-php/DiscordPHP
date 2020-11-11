<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

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
