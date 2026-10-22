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

/**
 * @internal Validated local IPC configuration and deterministic path discovery.
 *
 * @since 10.69.0
 */
final class IpcOptions
{
    private array $options;

    /**
     * Path discovery is a stateless utility; no external service is accessed.
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public function __construct(array $options = [])
    {
        $defaults = [
            'paths' => self::candidatePaths(),
            'connect_timeout' => 10.0,
            'max_frame_bytes' => 1048576,
            'max_buffer_bytes' => 2097168,
            'max_pending_requests' => 128,
            'max_queue_bytes' => 4194304,
            'frames_per_tick' => 32,
        ];
        if (array_diff_key($options, $defaults)) {
            throw new \InvalidArgumentException('Unknown IPC option.');
        }
        $this->options = $options + $defaults;
        $this->validateResources();
        $this->validateScheduling();
        $this->validatePaths();
    }

    public function values(): array
    {
        return $this->options;
    }

    /**
     * Local URIs in documented environment-prefix/index order.
     * The shared directory filter is a stateless path utility.
     *
     * @return string[]
     *
     * @SuppressWarnings("PHPMD.StaticAccess")
     */
    public static function candidatePaths(?array $environment = null, ?bool $windows = null): array
    {
        if ($windows ?? PHP_OS_FAMILY === 'Windows') {
            return array_map(static fn ($index) => '\\\\?\\pipe\\discord-ipc-'.$index, range(0, 9));
        }
        $paths = [];
        foreach (self::unixDirectories($environment) as $directory) {
            foreach (range(0, 9) as $index) {
                $paths[] = 'unix://'.$directory.'/discord-ipc-'.$index;
            }
        }

        return $paths;
    }

    private static function unixDirectories(?array $environment): array
    {
        $directories = [];
        foreach (['XDG_RUNTIME_DIR', 'TMPDIR', 'TMP', 'TEMP'] as $name) {
            $value = $environment === null ? getenv($name) : ($environment[$name] ?? false);
            if (is_string($value) && str_starts_with($value, '/') && ! str_contains($value, "\0")) {
                $directories[] = rtrim($value, '/');
            }
        }
        $directories[] = '/tmp';

        return array_unique($directories);
    }

    private function validateResources(): void
    {
        foreach (['max_frame_bytes', 'max_buffer_bytes', 'max_pending_requests', 'max_queue_bytes', 'frames_per_tick'] as $key) {
            if (! is_int($this->options[$key]) || $this->options[$key] < 1 || $this->options[$key] > 67108864) {
                throw new \InvalidArgumentException('Invalid IPC resource limit.');
            }
        }
        if ($this->options['max_buffer_bytes'] < $this->options['max_frame_bytes'] + 8) {
            throw new \InvalidArgumentException('Invalid IPC decoder limits.');
        }
    }

    private function validateScheduling(): void
    {
        if ($this->options['max_pending_requests'] > 10000 || $this->options['frames_per_tick'] > 1024 || ! is_numeric($this->options['connect_timeout']) || ! is_finite((float) $this->options['connect_timeout']) || $this->options['connect_timeout'] <= 0) {
            throw new \InvalidArgumentException('Invalid IPC scheduling limit.');
        }
    }

    private function validatePaths(): void
    {
        $paths = $this->options['paths'];
        if (! is_array($paths) || ! array_is_list($paths) || ! $paths || count($paths) > 50) {
            throw new \InvalidArgumentException('Provide between one and fifty local IPC paths.');
        }
        foreach ($paths as $path) {
            $this->validatePath($path);
        }
    }

    private function validatePath($path): void
    {
        if (! is_string($path) || str_contains($path, "\0") || ! (str_starts_with($path, 'unix:///') || preg_match('~^\\\\\\\\[?.]\\\\pipe\\\\discord-ipc-[0-9]$~D', $path))) {
            throw new \InvalidArgumentException('Only local Unix sockets or Discord named pipes are accepted.');
        }
    }
}
