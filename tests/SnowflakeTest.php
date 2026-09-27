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

use Discord\Helpers\Snowflake;

final class SnowflakeTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Helpers\Snowflake::__construct
     * @covers \Discord\Helpers\Snowflake::__toString
     */
    public function testParsesSnowflakeComponents(): void
    {
        $snowflake = new Snowflake('175928847299117063');

        $this->assertSame('175928847299117063', $snowflake->id);
        $this->assertSame(1462015105796, $snowflake->timestamp);
        $this->assertSame('2016-04-30 11:18:25', $snowflake->datetime->format('Y-m-d H:i:s'));
        $this->assertSame(1, $snowflake->worker_id);
        $this->assertSame(0, $snowflake->process_id);
        $this->assertSame(7, $snowflake->increment);
        $this->assertSame('175928847299117063', (string) $snowflake);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::new
     */
    public function testCreatesSnowflakeWithFactory(): void
    {
        $snowflake = Snowflake::new(175928847299117063);

        $this->assertSame('175928847299117063', $snowflake->id);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::fromTimestamp
     */
    public function testCreatesSnowflakeFromTimestamp(): void
    {
        $snowflake = Snowflake::fromTimestamp(1462015105796, 1, 0, 7);

        $this->assertSame('175928847299117063', $snowflake->id);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::fromTimestamp
     */
    public function testCreatesSnowflakeFromDateTime(): void
    {
        $snowflake = Snowflake::fromTimestamp(new DateTimeImmutable('@1462015105'));

        $this->assertSame(1462015105000, $snowflake->timestamp);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::fromTimestamp
     */
    public function testRejectsTimestampBeforeDiscordEpoch(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Snowflake::fromTimestamp(0);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::fromTimestamp
     */
    public function testRejectsOutOfRangeWorkerId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Snowflake::fromTimestamp(Snowflake::DISCORD_EPOCH, 32);
    }

    /**
     * @covers \Discord\Helpers\Snowflake::setId
     */
    public function testRejectsInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Snowflake('not-a-snowflake');
    }
}
