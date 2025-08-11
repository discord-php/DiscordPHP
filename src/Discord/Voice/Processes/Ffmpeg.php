<?php

declare(strict_types=1);

namespace Discord\Voice\Processes;

use Discord\Exceptions\FFmpegNotFoundException;
use React\ChildProcess\Process;

/**
 * Handles the decoding and encoding of audio streams using FFmpeg.
 *
 * @since 10.19.0
 */
final class Ffmpeg extends ProcessAbstract
{
    protected static string $exec = '/usr/bin/ffmpeg';

    public function __construct()
    {
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
    ): Process {
        $flags = [
            '-i', $filename ?? 'pipe:0',
            '-map_metadata', '-1',
            '-f', 'opus',
            '-c:a', 'libopus',
            '-ar', parent::DEFAULT_KHZ,
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

    /**
     * Decodes an Opus audio stream to OGG format using FFmpeg.
     *
     * TODO: Add support for Windows, currently only tested and ran on WSL2
     *
     * @param mixed $filename If there's no name, it will output to stdout
     *                        (pipe:1). If a name is given, it will save the file
     *                        with the given name. If the name does not end with
     *                        .ogg, it will append .ogg to the name.
     *                        If null, it will use 'pipe:1' as the filename.
     * @param int|float $volume Default: 0
     * @param int $bitrate Default: 128000
     * @param int $channels Default: 2
     * @param null|int $frameSize
     * @param null|array $preArgs
     * @return Process
     */
    public static function decode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        int $channels = 2,
        ?int $frameSize = null,
        ?array $preArgs = null,
    ): Process {
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
            '-loglevel', 'error', // Set log level to warning to reduce output noise
            '-channel_layout', 'stereo',
            '-ac', $channels,
            '-ar', parent::DEFAULT_KHZ,
            '-f', 's16le',
            '-i', 'pipe:0',
            '-acodec', 'libopus',
            '-f', 'ogg',
            '-ar', parent::DEFAULT_KHZ,
            '-ac', $channels,
            '-b:a', $bitrate,
            $filename
        ];

        if (null !== $preArgs) {
            $flags = array_merge($preArgs, $flags);
        }

        $flags = implode(' ', $flags);

        return new Process(self::$exec . " {$flags}");
    }
}
