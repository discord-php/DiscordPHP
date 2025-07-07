<?php

declare(strict_types=1);

namespace Discord\Voice\Processes;

use React\ChildProcess\Process;

/**
 * Handles the encoding and decoding of audio streams using DCA format.
 *
 * @since 10.19.0
 */
final class DCA extends ProcessAbstract
{
    /**
     * The DCA version the client is using.
     *
     * @var string The DCA version.
     */
    public const DCA_VERSION = 'DCA1';

    protected static string $exec = 'dca';

    public static function checkForDca(): bool
    {
        $binaries = [
            'dca',
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

    /**
     * Encodes audio to DCA format.
     *
     * @param string|null $filename The input filename, or null for pipe input.
     * @param int|float $volume The volume adjustment in dB.
     * @param int $bitrate The bitrate for the output audio.
     * @param array|null $preArgs Additional arguments to pass before the main flags.
     *
     * @TODO Implement function, was not in original code.
     *
     * @return Process
     */
    public static function encode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        ?array $preArgs = null
    ): Process {
        $flags = [
            '-ab', round($bitrate / 1000), // Bitrate
            '-mode', 'decode', // Decode mode
        ];

        $flags = implode(' ', $flags);

        return new Process(self::$exec . " $flags");
    }

    public static function decode(
        ?string $filename = null,
        int|float $volume = 0,
        int $bitrate = 128000,
        int $channels = 2,
        ?int $frameSize = null,
        ?array $preArgs = null,
    ): Process {
        if (null === $frameSize) {
            $frameSize = round($frameSize * 48);
        }

        $flags = [
        ];

        $flags = implode(' ', $flags);

        return new Process(self::$exec . " $flags");
    }
}
