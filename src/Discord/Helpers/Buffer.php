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

use Discord\Exceptions\BufferTimedOutException;
use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Stream\WritableStreamInterface;

/**
 * @since 6.0.0
 */
class Buffer extends EventEmitter implements WritableStreamInterface
{
    /**
     * Internal buffer.
     *
     * @var string
     */
    private $buffer = '';

    /**
     * Array of deferred reads waiting to be resolved.
     *
     * @var Deferred[]|int[]
     */
    private $reads = [];

    /**
     * Whether the buffer has been closed.
     *
     * @var bool
     */
    private $closed = false;

    /**
     * ReactPHP event loop.
     * Required for timeouts.
     *
     * @var LoopInterface
     */
    private $loop;
    
    public function __construct(LoopInterface $loop = null)
    {
        $this->loop = $loop;
    }

    /**
     * {@inheritDoc}
     */
    public function write($data): bool
    {
        if ($this->closed) {
            return false;
        }

        $this->buffer .= (string) $data;

        foreach ($this->reads as $key => [$deferred, $length]) {
            if (($output = $this->readRaw($length)) !== false) {
                $deferred->resolve($output);
                unset($this->reads[$key]);
            }
        }

        return true;
    }

    /**
     * Reads from the buffer and returns in a string.
     * Returns false if there were not enough bytes in the buffer.
     *
     * @param int $length Number of bytes to read.
     *
     * @return string|bool The bytes read, or false if not enough bytes are present.
     */
    private function readRaw(int $length)
    {
        if (strlen($this->buffer) >= $length) {
            $output = substr($this->buffer, 0, $length);
            $this->buffer = substr($this->buffer, $length);

            return $output;
        }

        return false;
    }

    /**
     * Reads from the buffer and returns a promise.
     * The promise will resolve when there are enough bytes in the buffer to
     * read.
     *
     * @param int         $length  Number of bytes to read.
     * @param null|string $format  Format to read the bytes in. See `pack()`.
     * @param int         $timeout Time in milliseconds before the read times out.
     *
     * @return ExtendedPromiseInterface<mixed, \RuntimeException>
     *
     * @throws \RuntimeException When there is an error unpacking the read bytes.
     */
    public function read(int $length, ?string $format = null, ?int $timeout = -1): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if (($output = $this->readRaw($length)) !== false) {
            $deferred->resolve($output);
        } else {
            $this->reads[] = [$deferred, $length];

            if ($timeout > 0 && $this->loop !== null) {
                $timer = $this->loop->addTimer($timeout / 1000, function () use ($deferred) {
                    $deferred->reject(new BufferTimedOutException());
                });

                $deferred->promise()->then(function () use ($timer) {
                    $this->loop->cancelTimer($timer);
                });
            } elseif ($timeout == 0) {
                $deferred->reject(new BufferTimedOutException());
            }
        }

        return $deferred->promise()->then(function ($d) use ($format) {
            if ($format !== null) {
                $unpacked = unpack($format, $d);
                
                if ($unpacked === false) {
                    throw new \RuntimeException('Error unpacking buffer.');
                }
                
                return reset($unpacked);
            }

            return $d;
        });
    }

    /**
     * Reads a signed 32-bit integer from the buffer.
     *
     * @param int $timeout Time in milliseconds before the read times out.
     *
     * @return ExtendedPromiseInterface<int, \RuntimeException>
     *
     * @throws \RuntimeException When there is an error unpacking the read bytes.
     */
    public function readInt32(int $timeout = -1): ExtendedPromiseInterface
    {
        return $this->read(4, 'l', $timeout);
    }

    /**
     * Reads a signed 16-bit integer from the buffer.
     *
     * @param int $timeout Time in milliseconds before the read times out.
     *
     * @return ExtendedPromiseInterface<int, \RuntimeException>
     *
     * @throws \RuntimeException When there is an error unpacking the read bytes.
     */
    public function readInt16(int $timeout = -1): ExtendedPromiseInterface
    {
        return $this->read(2, 'v', $timeout);
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable()
    {
        return $this->closed;
    }

    /**
     * {@inheritDoc}
     */
    public function end($data = null): void
    {
        $this->write($data);
        $this->close();
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        $this->buffer = [];
        $this->closed = true;
        $this->emit('close');
    }
}
