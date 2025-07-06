<?php

declare(strict_types=1);

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

        if ($filename) {
            $filename = date('Y-m-d_H-i') . '-' . $filename;
            if (!str_ends_with($filename, '.ogg')) {
                $filename .= '.ogg';
            }
        } elseif (null === $filename) {
            $filename = 'pipe:1';
        }

        $flags = [
            '-loglevel', 'warning', // Set log level to warning to reduce output noise
            '-channel_layout', 'stereo',
            '-ac', $channels,
            '-ar', '48000',
            '-f', 's16le',
            '-i', 'pipe:0',
            '-acodec', 'libopus',
            '-f', 'ogg',
            '-ar', '48000',
            '-ac', $channels,
            '-b:a', $bitrate,
            $filename
        ];

        if (null !== $preArgs) {
            $flags = array_merge($preArgs, $flags);
        }

        $flags = implode(' ', $flags);

        return new Process(self::$exec . " {$flags}",
            fds: str_contains(PHP_OS, 'Win') ? [
                ['socket'],
                ['socket'],
                ['socket'],
            ] : []
        );
    }
}
