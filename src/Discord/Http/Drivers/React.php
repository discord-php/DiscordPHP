<?php

namespace Discord\Http\Drivers;

use Discord\Helpers\Deferred;
use Discord\Http\DriverInterface;
use Exception;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Http\Message\ResponseException;
use React\Promise\ExtendedPromiseInterface;
use React\Socket\Connector;

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
     * @param array $options
     */
    public function __construct(LoopInterface $loop, array $options = [])
    {
        $this->loop = $loop;
        $this->browser = new Browser($loop, new Connector($loop, $options));
    }

    public function runRequest(string $method, string $url, string $content, array $headers): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        $this->browser->{$method}($url, $headers, $content ?? '')->done([$deferred, 'resolve'], function (Exception $e) use ($deferred) {
            if ($e instanceof ResponseException) {
                $deferred->resolve($e->getResponse());
            } else {
                $deferred->reject($e);
            }
        });

        return $deferred->promise();
    }
}
