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

use PHPUnit\Framework\TestCase;

final class OfflineTransportTest extends TestCase
{
    public function testOfflineStartupDoesNotInvokeRealTransports(): void
    {
        $command = [PHP_BINARY];
        if ($ini = php_ini_loaded_file()) {
            $command[] = '-c';
            $command[] = $ini;
        }
        $command[] = __DIR__.'/offline-transport-probe.php';
        $environment = ['DISCORD_TOKEN' => ''];
        foreach (['SYSTEMROOT', 'WINDIR', 'PATH', 'TEMP', 'TMP', 'TMPDIR'] as $name) {
            if (($value = getenv($name)) !== false) {
                $environment[$name] = $value;
            }
        }
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, dirname(__DIR__, 2), $environment);
        $this->assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors);
        $this->assertStringContainsString('offline startup made zero HTTP/WebSocket transport calls', $output);
    }
}
