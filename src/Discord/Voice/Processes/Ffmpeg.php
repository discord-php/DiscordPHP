<?php

namespace Discord\Voice\Processes;

use Discord\Exceptions\FFmpegNotFoundException;
use Discord\Voice\Processes\ProcessAbstract;
use React\ChildProcess\Process;

final class Ffmpeg extends ProcessAbstract
{
    protected static string $exec = '/usr/bin/ffmpeg';

    public function __construct(
    ) {
        if (!$this->checkForFFmpeg()) {
            throw new FFmpegNotFoundException('FFmpeg binary not found.');
        }
    }

    public static function __callStatic(string $name, array $arguments)
    {
        if (method_exists(self::class, $name) && in_array($name, ['encode', 'decode'])) {
            if (! self::checkForFFmpeg()) {
                throw new FFmpegNotFoundException('FFmpeg binary not found.');
            }

            return self::$name(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist in " . __CLASS__);
    }

    public static function checkForFFmpeg(): bool
    {
        $binaries = [
            'ffmpeg',
        ];

        foreach ($binaries as $binary) {
            $output = self::checkForExecutable($binary);

            if (null !== $output) {
                self::$exec = $output;

                return true;
            }
        }

        return false;
    }

    public static function encode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        ?array $preArgs = null
    ): Process
    {
        $flags = [
            '-i', $filename ?? 'pipe:0',
            '-map_metadata', '-1',
            '-f', 'opus',
            '-c:a', 'libopus',
            '-ar', '48000',
            '-af', "volume={$volume}dB",
            '-ac', '2',
            '-b:a', $bitrate,
            '-loglevel', 'warning',
            'pipe:1',
        ];

        if (null !== $preArgs) {
            $flags = array_merge($preArgs, $flags);
        }

        $flags = implode(' ', $flags);
        $cmd = self::$exec . " {$flags}";

        return new Process(
            $cmd,
            fds: [
                ['socket'],
                ['socket'],
                ['socket'],
            ]
        );
    }

    public static function decode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        int $channels = 2,
        ?int $frameSize = null,
        ?array $preArgs = null,
    ): Process
    {
        if (null === $frameSize) {
            $frameSize = round(20 * 48);
        }

        $flags = [
            '-ac:opus', $channels, // Channels
            '-ab', round($bitrate / 1000), // Bitrate
            '-as', $frameSize, // Frame Size
            '-ar', '48000', // Audio Rate
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        return new Process(
            self::$exec . " {$flags}",
            fds: [
                ['socket'],
                ['socket'],
                ['socket'],
            ]
        );
    }
}
