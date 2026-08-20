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

use Discord\Parts\Part;

use DateTimeInterface;
use Discord\Discord;
use InvalidArgumentException;
use ReflectionClass;
use Stringable;

/**
 * Timestamp is a helper class for formatting timestamps in Discord messages.
 *
 * https://docs.discord.com/developers/reference#message-formatting
 *
 * @since 10.56.6
 *
 * @author Valithor Obsidion <valithor@discordphp.org>
 *
 * @property int    $timestamp Unix timestamp (in seconds) that will be displayed.
 * @property string $format    The Discord timestamp style, one of the `Timestamp::*` constants. Defaults to `Timestamp::STYLE_SHORT_DATE_TIME`.
 */
class Timestamp extends Part implements Stringable
{
    /** Short Time, e.g. `16:20`. */
    public const STYLE_SHORT_TIME = 't';

    /** Long Time, e.g. `16:20:30`. */
    public const STYLE_LONG_TIME = 'T';

    /** Short Date, e.g. `20/04/2021`. */
    public const STYLE_SHORT_DATE = 'd';

    /** Long Date, e.g. `April 20, 2021`. */
    public const STYLE_LONG_DATE = 'D';

    /** Short Date/Time, e.g. `20 April 2021 16:20`. Default. */
    public const STYLE_SHORT_DATE_TIME = 'f';

    /** Long Date/Time, e.g. `Tuesday, April 20, 2021 16:20`. */
    public const STYLE_LONG_DATE_TIME = 'F';

    /** Short Date, Short Time, e.g. `20/04/2021, 16:20`. */
    public const STYLE_SHORT_DATE_SHORT_TIME = 's';

    /** Short Date, Medium Time, e.g. `20/04/2021, 16:20:30`. */
    public const STYLE_SHORT_DATE_MEDIUM_TIME = 'S';

    /** Relative Time, e.g. `4 years ago`. */
    public const STYLE_RELATIVE_TIME = 'R';

    /**
     * All valid Discord timestamp styles.
     *
     * @var string[]
     */
    public const STYLES = [
        self::STYLE_SHORT_TIME,
        self::STYLE_LONG_TIME,
        self::STYLE_SHORT_DATE,
        self::STYLE_LONG_DATE,
        self::STYLE_SHORT_DATE_TIME,
        self::STYLE_LONG_DATE_TIME,
        self::STYLE_SHORT_DATE_SHORT_TIME,
        self::STYLE_SHORT_DATE_MEDIUM_TIME,
        self::STYLE_RELATIVE_TIME,
    ];

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'timestamp',
        'format',
    ];

    /**
     * Creates a new Timestamp instance.
     *
     * @param Discord                      $discord   The Discord client instance.
     * @param DateTimeInterface|int|string $timestamp A `DateTimeInterface` (e.g. `DateTime`, `Carbon`) or a Unix timestamp in seconds.
     * @param string                       $format    One of the `Timestamp::*` style constants.
     */
    public static function new(Discord $discord, $timestamp, string $format = self::STYLE_SHORT_DATE_TIME)
    {
        $instance = new self($discord);
        $instance->timestamp = $timestamp;
        $instance->format = $format;

        return $instance;
    }

    /**
     * Normalizes and sets the `timestamp` attribute from a `DateTimeInterface` or Unix timestamp.
     *
     * @param DateTimeInterface|int|string $timestamp
     */
    protected function setTimestampAttribute($timestamp): void
    {
        if ($timestamp instanceof DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        } elseif (! is_numeric($timestamp)) {
            throw new InvalidArgumentException('Timestamp must be a DateTimeInterface (e.g. DateTime, Carbon) or a Unix timestamp.');
        }

        $this->attributes['timestamp'] = (int) $timestamp;
    }

    /**
     * Sets the `format` attribute, ensuring it is one of the valid Discord timestamp styles.
     *
     * @param string $format
     *
     * @throws \InvalidArgumentException
     */
    protected function setFormatAttribute(string $format): void
    {
        $styles = array_values((new ReflectionClass(Timestamp::class))->getConstants());

        if (! in_array($format, $styles, true)) {
            throw new InvalidArgumentException('Format must be one of the Timestamp styles.');
        }

        $this->attributes['format'] = $format;
    }

    /**
     * Converts the timestamp to Discord's `<t:UNIX_TIMESTAMP:FORMAT>` markdown.
     *
     * @link https://discord.com/developers/docs/reference#message-formatting
     */
    public function __toString(): string
    {
        return sprintf('<t:%d:%s>', $this->timestamp, $this->format);
    }
}
