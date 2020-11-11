<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Http\Drivers;

use Discord\Http\DriverInterface;
use Discord\Http\Request;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\ExtendedPromiseInterface;
use React\Socket\Connector;

/**
 * react/http driver for Discord HTTP client.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class React implements DriverInterface
{
    /**
     * ReactPHP event loop.
     *
     * @var LoopInterface
     */
    protected $loop;

    /**
     * ReactPHP/HTTP browser.
     *
     * @var Browser
     */
    protected $browser;

    /**
     * Constructs the Guzzle driver.
     *
     * @param LoopInterface $loop
     * @param array         $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;

        // Allow 400 and 500 HTTP requests to be resolved rather than rejected.
        $browser = new Browser($loop, new Connector($loop, $options));
        $this->browser = $browser->withRejectErrorResponse(false);
    }

    public function runRequest(Request $request): ExtendedPromiseInterface
    {
        return $this->browser->{$request->getMethod()}(
            $request->getUrl(),
            $request->getHeaders(),
            $request->getContent()
        );
    }
}
