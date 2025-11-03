<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Evenement\EventEmitter;

/** 
 * A simple implementation of a Windows-compatible process handler.
 * 
 * Experimental and minimal; for demonstration and testing purposes only.
 * 
 * @see React\ChildProcess\Process
 */
class WinProcess extends EventEmitter
{
    public $stdin;
    public $stdout;
    public $stderr;
    private $proc;
    private $pipes;
    private string $cmd;

    public function __construct(string $cmd)
    {
        $this->cmd = $cmd;
    }

    public function start($loop = null): void
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $this->proc = proc_open($this->cmd, $descriptorSpec, $this->pipes);

        if (! is_resource($this->proc)) {
            $this->emit('error', [new \RuntimeException('Failed to start process.')]);

            return;
        }

        [$this->stdin, $this->stdout, $this->stderr] = $this->pipes;

        // Non-blocking
        stream_set_blocking($this->stdout, false);
        stream_set_blocking($this->stderr, false);

        // Optional React event loop integration
        if ($loop !== null) {
            $loop->addReadStream($this->stdout, function () {
                $data = fread($this->stdout, 8192);
                if ($data !== false && $data !== '') {
                    $this->emit('data', [$data]);
                }
            });

            $loop->addReadStream($this->stderr, function () {
                $data = fread($this->stderr, 8192);
                if ($data !== false && $data !== '') {
                    $this->emit('errorOutput', [$data]);
                }
            });

            // Periodically check if the process exited
            $loop->addPeriodicTimer(0.5, function ($timer) use ($loop) {
                if (! $this->isRunning()) {
                    $exitCode = $this->close();
                    $this->emit('exit', [$exitCode]);
                    $loop->cancelTimer($timer);
                }
            });
        }
    }

    public function terminate(): void
    {
        if (is_resource($this->proc)) {
            proc_terminate($this->proc);
            $this->emit('exit', [0]);
        }
    }

    public function isRunning(): bool
    {
        if (! is_resource($this->proc)) {
            return false;
        }

        $status = proc_get_status($this->proc);

        return $status['running'];
    }

    public function close(): int
    {
        foreach ([$this->stdin, $this->stdout, $this->stderr] as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        $code = is_resource($this->proc)
            ? proc_close($this->proc)
            : 0;

        $this->emit('exit', [$code]);

        return $code;
    }
}
