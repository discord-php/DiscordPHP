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

namespace Discord\Helpers;

use Carbon\Carbon;
use DateTimeInterface;
use GMP;
use InvalidArgumentException;
use Stringable;

/**
 * Snowflake is a helper class for parsing Discord snowflake IDs into their component parts, and for generating snowflake IDs from a given timestamp.
 *
 * https://docs.discord.com/developers/reference#snowflakes
 *
 * @since 10.56.9
 *
 * @author Valithor Obsidion <valithor@discordphp.org>
 *
 * @property-read string     $id         The snowflake ID as a numeric string.
 * @property-read int|string $timestamp  Milliseconds since the Unix Epoch that the snowflake was generated at. Returned as a numeric string on 32-bit PHP, where it does not fit in a native `int`.
 * @property-read Carbon     $datetime   The `Carbon` instance the snowflake was generated at.
 * @property-read int        $worker_id  Internal worker ID, 0-31.
 * @property-read int        $process_id Internal process ID, 0-31.
 * @property-read int        $increment  Increment for the ID generated on that worker/process, 0-4095.
 */
class Snowflake implements Stringable
{
    use DynamicPropertyMutatorTrait;

    /** Discord Epoch (2015-01-01T00:00:00Z), in milliseconds since the Unix Epoch. */
    public const DISCORD_EPOCH = 1420070400000;

    /** The snowflake ID as a numeric string. */
    protected string $id;

    /**
     * @param Stringable|int|string $id The snowflake ID.
     */
    public function __construct($id)
    {
        if (PHP_INT_SIZE === 4) {
            BigInt::init();
        }

        $this->setId($id);
    }

    /**
     * @param Stringable|int|string $id The snowflake ID.
     */
    public static function new($id): self
    {
        return new self($id);
    }

    /**
     * Creates a new Snowflake from a timestamp and optional internal fields.
     *
     * @param DateTimeInterface|int|string $timestamp A `DateTimeInterface` or a Unix timestamp in milliseconds.
     * @param int                          $workerId  Internal worker ID, 0-31. Defaults to 0.
     * @param int                          $processId Internal process ID, 0-31. Defaults to 0.
     * @param int                          $increment Increment for the ID, 0-4095. Defaults to 0.
     *
     * @throws InvalidArgumentException
     */
    public static function fromTimestamp($timestamp, int $workerId = 0, int $processId = 0, int $increment = 0): self
    {
        $timestamp = self::normalizeTimestampToMs($timestamp);

        if ($timestamp < self::DISCORD_EPOCH) {
            throw new InvalidArgumentException('Timestamp cannot be before the Discord Epoch ('.self::DISCORD_EPOCH.').');
        }

        if ($workerId < 0 || $workerId > 0x1F) {
            throw new InvalidArgumentException('Worker ID must be between 0 and 31.');
        }

        if ($processId < 0 || $processId > 0x1F) {
            throw new InvalidArgumentException('Process ID must be between 0 and 31.');
        }

        if ($increment < 0 || $increment > 0xFFF) {
            throw new InvalidArgumentException('Increment must be between 0 and 4095.');
        }

        $id = BigInt::shiftLeft(BigInt::sub($timestamp, self::DISCORD_EPOCH), 22);
        $id = BigInt::or($id, BigInt::shiftLeft($workerId, 17));
        $id = BigInt::or($id, BigInt::shiftLeft($processId, 12));
        $id = BigInt::or($id, $increment);

        return new self($id instanceof GMP ? gmp_strval($id) : (string) $id);
    }

    /**
     * Normalizes a `DateTimeInterface` or numeric timestamp to milliseconds since the Unix Epoch.
     *
     * Returned as a numeric string on 32-bit PHP, since millisecond timestamps overflow a native `int` there.
     *
     * @param DateTimeInterface|int|string $timestamp
     *
     * @return int|string
     */
    protected static function normalizeTimestampToMs($timestamp)
    {
        if ($timestamp instanceof DateTimeInterface) {
            $ms = round(((float) $timestamp->format('U.u')) * 1000);

            return PHP_INT_SIZE === 4 ? sprintf('%.0f', $ms) : (int) $ms;
        }

        if (! is_numeric($timestamp)) {
            throw new InvalidArgumentException('Timestamp must be a DateTimeInterface or a Unix timestamp in milliseconds.');
        }

        return PHP_INT_SIZE === 4 ? sprintf('%.0f', (float) $timestamp) : (int) $timestamp;
    }

    /**
     * @return string The snowflake ID as a numeric string.
     */
    protected function getId(): string
    {
        return $this->id;
    }

    /**
     * Normalizes and sets the `id` attribute.
     *
     * @param Stringable|int|string $id
     */
    protected function setId($id): void
    {
        $id = (string) $id;

        if (! ctype_digit($id)) {
            throw new InvalidArgumentException('Snowflake ID must be a numeric string or integer.');
        }

        $this->id = $id;
    }

    /**
     * Returned as a numeric string on 32-bit PHP, since the value overflows a native `int` there.
     *
     * @return int|string Milliseconds since the Unix Epoch that the snowflake was generated at.
     */
    protected function getTimestamp()
    {
        $ms = BigInt::add(BigInt::shiftRight($this->id, 22), self::DISCORD_EPOCH);

        return $ms instanceof GMP ? gmp_strval($ms) : (int) $ms;
    }

    /**
     * @return Carbon The datetime the snowflake was generated at.
     */
    protected function getDatetime(): Carbon
    {
        return Carbon::createFromTimestampMs($this->getTimestamp());
    }

    /**
     * @return int Internal worker ID, 0-31.
     */
    protected function getWorkerId(): int
    {
        return (int) BigInt::shiftRight(BigInt::and($this->id, 0x3E0000), 17);
    }

    /**
     * @return int Internal process ID, 0-31.
     */
    protected function getProcessId(): int
    {
        return (int) BigInt::shiftRight(BigInt::and($this->id, 0x1F000), 12);
    }

    /**
     * @return int Increment for the ID generated on that worker/process, 0-4095.
     */
    protected function getIncrement(): int
    {
        return (int) BigInt::and($this->id, 0xFFF);
    }

    /**
     * @return string The snowflake ID.
     */
    public function __toString(): string
    {
        return $this->id;
    }
}
