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

use Discord\Parts\Part;
use Discord\Repository\Guild\MemberRepository;
use Psr\Log\NullLogger;

final class PartAttributeDispatchTest extends DiscordTestCase
{
    public function testAttributeDispatchPreservesRepositoryAndGetterPrecedence(): void
    {
        $discord = $this->getMockBuilder(OfflineDiscordClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connectWs'])
            ->getMock();
        $discord->expects($this->once())->method('connectWs');
        $discord->__construct(['token' => '', 'logger' => new NullLogger(), 'socket_options' => ['dns' => false]]);

        $part = new class($discord, ['guild_id' => '10', 'members' => 'raw', 'computed_name' => 'raw'], true) extends Part {
            protected $fillable = ['guild_id', 'members', 'computed_name'];

            protected $repositories = ['members' => MemberRepository::class];

            protected function getMembersAttribute(): string
            {
                throw new \LogicException('The repository must take precedence.');
            }

            protected function getComputedNameAttribute(): string
            {
                return strtoupper($this->attributes['computed_name']);
            }
        };

        $this->assertInstanceOf(MemberRepository::class, $part->members);
        $this->assertSame($part->members, $part['members']);
        $this->assertSame('RAW', $part->computed_name);
        $this->assertSame('RAW', $part->{'computed-name'});
        $this->assertSame('RAW', $part->computedName);
        $this->assertSame('raw', $part->getRawAttributes()['computed_name']);
        $this->assertNull($part->unknown);
        $this->assertFalse(isset($part['unknown']));
    }
}
