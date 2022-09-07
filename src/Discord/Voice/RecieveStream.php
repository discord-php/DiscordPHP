<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Voice;

use Evenement\EventEmitter;
use React\Stream\DuplexStreamInterface;
use React\Stream\WritableStreamInterface;

/**
 * Handles recieving audio from Discord.
 */
class RecieveStream extends EventEmitter implements DuplexStreamInterface
{
    /**
     * Contains PCM data.
     *
     * @var string PCM data.
     */
    protected $pcmData = '';

    /**
     * Contains Opus data.
     *
     * @var string Opus data.
     */
    protected $opusData = '';

    /**
     * Is the stream paused?
     *
     * @var bool Whether the stream is paused.
     */
    protected $isPaused;

    /**
     * Whether the stream is closed.
     *
     * @var bool Whether the stream is closed.
     */
    protected $isClosed = false;

    /**
     * The PCM pause buffer.
     *
     * @var array The PCM pause buffer.
     */
    protected $pcmPauseBuffer = [];

    /**
     * The pause buffer.
     *
     * @var array The pause buffer.
     */
    protected $opusPauseBuffer = [];

    /**
     * Constructs a stream.
     */
    public function __construct()
    {
        // empty for now
    }

    /**
     * Writes PCM audio data.
     *
     * @param string $pcm PCM audio data.
     */
    public function writePCM(string $pcm): void
    {
        if ($this->isClosed) {
            return;
        }

        if ($this->isPaused) {
            $this->pcmPauseBuffer[] = $pcm;

            return;
        }

        $this->pcmData .= $pcm;

        $this->emit('pcm', [$pcm]);
    }

    /**
     * Writes Opus audio data.
     *
     * @param string $opus Opus audio data.
     */
    public function writeOpus(string $opus): void
    {
        if ($this->isClosed) {
            return;
        }

        if ($this->isPaused) {
            $this->opusPauseBuffer[] = $opus;

            return;
        }

        $this->opusData .= $opus;

        $this->emit('opus', [$opus]);
    }

    /**
     * @inheritdoc
     */
    public function isReadable()
    {
        return $this->isPaused;
    }

    /**
     * @inheritdoc
     */
    public function isWritable()
    {
        return $this->isPaused;
    }

    /**
     * @inheritdoc
     */
    public function write($data)
    {
        $this->writePCM($data);
    }

    /**
     * @inheritdoc
     */
    public function end($data = null)
    {
        if ($this->isClosed) {
            return;
        }

        $this->write($data);
        $this->close();
    }

    /**
     * @inheritdoc
     */
    public function close()
    {
        if ($this->isClosed) {
            return;
        }

        $this->pause();
        $this->emit('end', []);
        $this->emit('close', []);
        $this->isClosed = true;
    }

    /**
     * @inheritdoc
     */
    public function pause()
    {
        if ($this->isClosed) {
            return;
        }

        if ($this->isPaused) {
            return;
        }

        $this->isPaused = true;
    }

    /**
     * @inheritdoc
     */
    public function resume()
    {
        if ($this->isClosed) {
            return;
        }

        if (! $this->isPaused) {
            return;
        }

        $this->isPaused = false;

        foreach ($this->pcmPauseBuffer as $data) {
            $this->writePCM($data);
        }

        foreach ($this->opusPauseBuffer as $data) {
            $this->writeOpus($data);
        }
    }

    /**
     * @inheritdoc
     */
    public function pipe(WritableStreamInterface $dest, array $options = [])
    {
        $this->pipePCM($dest, $options);
    }

    /**
     * Pipes PCM to a destination stream.
     *
     * @param WritableStreamInterface $dest    The stream to pipe to.
     * @param array                   $options An array of options.
     */
    public function pipePCM(WritableStreamInterface $dest, array $options = []): void
    {
        if ($this->isClosed) {
            return;
        }

        $this->on('pcm', function ($data) use ($dest) {
            $feedmore = $dest->write($data);

            if (false === $feedmore) {
                $this->pause();
            }
        });

        $dest->on('drain', function () {
            $this->resume();
        });

        $end = isset($options['end']) ? $options['end'] : true;
        if ($end && $this !== $dest) {
            $this->on('end', function () use ($dest) {
                $dest->end();
            });
        }
    }

    /**
     * Pipes Opus to a destination stream.
     *
     * @param WritableStreamInterface $dest    The stream to pipe to.
     * @param array                   $options An array of options.
     */
    public function pipeOpus(WritableStreamInterface $dest, array $options = []): void
    {
        if ($this->isClosed) {
            return;
        }

        $this->on('opus', function ($data) use ($dest) {
            $feedmore = $dest->write($data);

            if (false === $feedmore) {
                $this->pause();
            }
        });

        $dest->on('drain', function () {
            $this->resume();
        });

        $end = isset($options['end']) ? $options['end'] : true;
        if ($end && $this !== $dest) {
            $this->on('end', function () use ($dest) {
                $dest->end();
            });
        }
    }
}
