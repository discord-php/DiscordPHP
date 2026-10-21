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

use Discord\RPC\IpcOptions;
use PHPUnit\Framework\Attributes\DataProvider;

/** @SuppressWarnings(PHPMD.StaticAccess) Tests the stateless local-path discovery API. */
final class IpcOptionsTest extends DiscordTestCase
{
    protected static function isLive(): bool
    {
        return false;
    }

    public function testDefaultsAndOverridesPreserveTypesAndValues(): void
    {
        $defaults = (new IpcOptions())->values();
        $this->assertCount(7, $defaults);
        $this->assertSame(IpcOptions::candidatePaths(), $defaults['paths']);
        $this->assertSame(10.0, $defaults['connect_timeout']);
        $this->assertSame(1048576, $defaults['max_frame_bytes']);
        $this->assertSame(2097168, $defaults['max_buffer_bytes']);
        $this->assertSame(128, $defaults['max_pending_requests']);
        $this->assertSame(4194304, $defaults['max_queue_bytes']);
        $this->assertSame(32, $defaults['frames_per_tick']);
        $overrides = ['paths' => ['unix:///synthetic/discord-ipc-0'], 'connect_timeout' => '2.5'];
        $this->assertSame($overrides + $defaults, (new IpcOptions($overrides))->values());
        $defaults['frames_per_tick'] = 1;
        $this->assertSame(32, (new IpcOptions())->values()['frames_per_tick']);
    }

    public function testResourceBoundariesAndNativePipePaths(): void
    {
        $options = ['max_frame_bytes' => 67108856, 'max_buffer_bytes' => 67108864, 'max_pending_requests' => 10000, 'max_queue_bytes' => 67108864, 'frames_per_tick' => 1024];
        $this->assertSame(67108856, (new IpcOptions($options))->values()['max_frame_bytes']);
        $minimal = ['max_frame_bytes' => 1, 'max_buffer_bytes' => 9, 'max_pending_requests' => 1, 'max_queue_bytes' => 1, 'frames_per_tick' => 1];
        $this->assertSame(9, (new IpcOptions($minimal))->values()['max_buffer_bytes']);
        $paths = IpcOptions::candidatePaths([], true);
        $this->assertCount(10, $paths);
        $this->assertSame('\\\\?\\pipe\\discord-ipc-0', $paths[0]);
        $this->assertSame('\\\\?\\pipe\\discord-ipc-9', $paths[9]);
        $paths[] = '\\\\.\\pipe\\discord-ipc-0';
        $this->assertSame($paths, (new IpcOptions(['paths' => $paths]))->values()['paths']);
    }

    #[DataProvider('invalidOptions')]
    public function testRejectsInvalidOptions(array $options): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IpcOptions($options);
    }

    public static function invalidOptions(): array
    {
        $invalid = [
            [['unknown' => true]], [['connect_timeout' => 0]], [['connect_timeout' => -1]],
            [['connect_timeout' => INF]], [['connect_timeout' => NAN]], [['connect_timeout' => 'no']],
            [['connect_timeout' => true]], [['max_pending_requests' => 10001]], [['frames_per_tick' => 1025]],
            [['max_frame_bytes' => 16, 'max_buffer_bytes' => 23]],
            [['paths' => []]], [['paths' => 'unix:///tmp/discord-ipc-0']],
            [['paths' => ['primary' => 'unix:///tmp/discord-ipc-0']]],
            [['paths' => [1 => 'unix:///tmp/discord-ipc-0']]],
            [['paths' => array_fill(0, 51, 'unix:///tmp/discord-ipc-0')]],
        ];
        foreach (['max_frame_bytes', 'max_buffer_bytes', 'max_pending_requests', 'max_queue_bytes', 'frames_per_tick'] as $key) {
            foreach ([0, -1, 67108865, 1.5, '1', null] as $value) {
                $invalid[] = [[$key => $value]];
            }
        }
        foreach (['tcp://127.0.0.1:6463', 'tls://example.com:443', 'unix://remote/discord-ipc-0', 'relative/discord-ipc-0', "unix:///tmp/discord\0-ipc-0", '\\\\?\\pipe\\discord-ipc-10', '\\\\?\\pipe\\other-0', null, 1] as $path) {
            $invalid[] = [['paths' => [$path]]];
        }

        return $invalid;
    }

    public function testUnixDiscoveryUsesPrefixThenIndexOrderAndDeduplicatesDirectories(): void
    {
        $paths = IpcOptions::candidatePaths(['XDG_RUNTIME_DIR' => '/runtime/', 'TMPDIR' => '/temp', 'TMP' => '/third', 'TEMP' => '/last'], false);
        $this->assertCount(50, $paths);
        $this->assertSame('unix:///runtime/discord-ipc-0', $paths[0]);
        $this->assertSame('unix:///runtime/discord-ipc-9', $paths[9]);
        $this->assertSame('unix:///temp/discord-ipc-0', $paths[10]);
        $this->assertSame('unix:///third/discord-ipc-0', $paths[20]);
        $this->assertSame('unix:///last/discord-ipc-0', $paths[30]);
        $this->assertSame('unix:///tmp/discord-ipc-9', $paths[49]);
        $fallback = IpcOptions::candidatePaths(['XDG_RUNTIME_DIR' => 'relative', 'TMPDIR' => "/bad\0dir", 'TMP' => false, 'TEMP' => '/tmp/'], false);
        $this->assertCount(10, $fallback);
        $this->assertSame('unix:///tmp/discord-ipc-0', $fallback[0]);
    }
}
