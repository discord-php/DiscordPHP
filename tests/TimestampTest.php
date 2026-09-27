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

use Discord\Helpers\Timestamp;

final class TimestampTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Helpers\Timestamp::__construct
     * @covers \Discord\Helpers\Timestamp::__toString
     */
    public function testCreatesTimestampWithDefaultFormat(): void
    {
        $timestamp = new Timestamp(1700000000);

        $this->assertSame(1700000000, $timestamp->timestamp);
        $this->assertSame(Timestamp::STYLE_SHORT_DATE_TIME, $timestamp->format);
        $this->assertSame('<t:1700000000:f>', (string) $timestamp);
    }

    /**
     * @covers \Discord\Helpers\Timestamp::new
     */
    public function testCreatesTimestampWithFactory(): void
    {
        $timestamp = Timestamp::new('1700000000', Timestamp::STYLE_RELATIVE_TIME);

        $this->assertSame(1700000000, $timestamp->timestamp);
        $this->assertSame(Timestamp::STYLE_RELATIVE_TIME, $timestamp->format);
        $this->assertSame('<t:1700000000:R>', (string) $timestamp);
    }

    /**
     * @covers \Discord\Helpers\Timestamp::setTimestamp
     */
    public function testNormalizesDateTimeTimestamp(): void
    {
        $timestamp = new Timestamp(new DateTimeImmutable('@1700000000'));

        $this->assertSame(1700000000, $timestamp->timestamp);
    }

    /**
     * @covers \Discord\Helpers\Timestamp::__set
     * @covers \Discord\Helpers\Timestamp::setTimestamp
     * @covers \Discord\Helpers\Timestamp::setFormat
     */
    public function testMagicPropertiesCanBeUpdated(): void
    {
        $timestamp = new Timestamp(1700000000);

        $timestamp->timestamp = '1700000001';
        $timestamp->format = Timestamp::STYLE_LONG_DATE_TIME;

        $this->assertSame(1700000001, $timestamp->timestamp);
        $this->assertSame(Timestamp::STYLE_LONG_DATE_TIME, $timestamp->format);
        $this->assertSame('<t:1700000001:F>', (string) $timestamp);
    }

    /**
     * @covers \Discord\Helpers\Timestamp::setTimestamp
     */
    public function testRejectsInvalidTimestamp(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Timestamp('not-a-timestamp');
    }

    /**
     * @covers \Discord\Helpers\Timestamp::setFormat
     */
    public function testRejectsInvalidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Timestamp(1700000000, 'invalid');
    }
}
