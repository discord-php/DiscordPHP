<?php

declare(strict_types=1);

namespace Discord\Voice\Processes;

use React\ChildProcess\Process;

/**
 * Abstract class for handling audio processing in Discord voice.
 *
 * This class provides methods to encode and decode audio streams using different processes.
 *
 * @since 10.19.0
 */
abstract class ProcessAbstract
{
    abstract public static function encode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        ?array $preArgs = null,
    ): Process;

    abstract public static function decode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        int $channels = 2,
        ?int $frameSize = null,
        ?array $preArgs = null,
    ): Process;

    public static function checkForExecutable(string $exec): ?string
    {
        $systemOs = substr(PHP_OS, 0, 3);
        $which = 'command -v';
        if (strtoupper($systemOs) === 'WIN') {
            $which = 'where';
        }
        $shellExecutable = shell_exec("$which $exec");
        $executable = rtrim((string) explode(PHP_EOL, $shellExecutable)[0]);

        return is_executable($executable) ? $executable : null;
    }
}
