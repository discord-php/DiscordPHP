<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\RPC;

use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;

/** @internal Spawns without shell or process pipes, and reaps only after observed exit. */
final class WindowsPipeProcess
{
    /** @var resource|null */
    private $process = null;
    private ?TimerInterface $poll = null;

    public function __construct(private LoopInterface $loop, private string $node, private array $environment, private \Closure $onExit)
    {
    }

    public function start(): void
    {
        // Never inherit NODE_OPTIONS, NODE_PATH, OAuth secrets or application environment.
        $env = $this->environment;
        foreach (['SystemRoot', 'TEMP', 'TMP'] as $key) {
            if (getenv($key) === false) {
                continue;
            }
            $env[$key] = getenv($key);
        }
        // No anonymous process pipes: these are not selectable on Windows. No shell or secret argv.
        // Reject with a sanitized error below; do not print executable or application paths.
        set_error_handler(static fn () => true);
        try {
            $this->process = proc_open([$this->node, __DIR__.'/bridge/windows-pipe.cjs'], [
                0 => ['file', 'NUL', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w'],
            ], $unused, null, $env, ['bypass_shell' => true, 'create_no_window' => true]);
        } finally {
            restore_error_handler();
        }
        $this->environment = [];
        if (! is_resource($this->process)) {
            throw new \RuntimeException('Windows pipe helper could not start.');
        }
        $this->poll = $this->loop->addPeriodicTimer(0.02, $this->check(...));
    }

    public function terminate(): void
    {
        if (is_resource($this->process)) {
            // Nonblocking. The timer remains alive to reap the process after exit.
            proc_terminate($this->process, 9);
        }
    }

    private function check(): void
    {
        if (proc_get_status($this->process)['running']) {
            return;
        }
        // proc_close can wait: only reap after the OS reports termination.
        proc_close($this->process);
        $this->process = null;
        $this->loop->cancelTimer($this->poll);
        $this->poll = null;
        ($this->onExit)();
    }
}
