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

use Discord\Discord;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Psr\Log\NullLogger;

final class PartNestedAttributeTest extends DiscordTestCase
{
    public function testEmbeddedPartsRetainTheirPayloadAndLifecycleRatherThanResolveTheCache(): void
    {
        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connectWs'])
            ->getMock();
        $discord->expects($this->once())->method('connectWs');
        $discord->__construct(['token' => '', 'logger' => new NullLogger(), 'socket_options' => ['dns' => false]]);
        $driver = getMockHttpDriver(function () {
            throw new \LogicException('Nested hydration must not fetch.');
        });
        $discord->getHttpClient()->setDriver($driver);

        $cached = $discord->getFactory()->part(Guild::class, ['id' => '10', 'name' => 'cached'], true);
        $discord->guilds->pushItem($cached);
        $part = new class($discord, ['guild_id' => '10'], true) extends Part {
            protected $fillable = ['guild_id', 'guild'];

            protected function getGuildAttribute(): ?Guild
            {
                return $this->attributePartHelper('guild', Guild::class);
            }
        };

        $this->assertNull($part->guild, 'The embedded helper does not resolve guild_id through the cache.');
        $this->assertFalse(isset($part->guild));
        $this->assertSame(['guild_id' => '10'], $part->__serialize());

        $part->guild = (object) ['id' => '10', 'name' => 'embedded'];
        $embedded = $part->guild;
        $this->assertInstanceOf(Guild::class, $embedded);
        $this->assertNotSame($cached, $embedded);
        $this->assertSame('embedded', $embedded->name);
        $this->assertSame($embedded, $part->guild);
        $this->assertSame($embedded, $part->getRawAttributes()['guild']);
        $this->assertSame($embedded, $part->__serialize()['guild']);
        $this->assertSame($cached, $discord->guilds->get('id', '10'));

        $part->created = false;
        $this->assertFalse($embedded->created, 'Hydrated children share their parent lifecycle.');
        $this->assertTrue($cached->created, 'Repository objects keep their independent lifecycle.');
        $this->assertSame([], $driver->requests);
    }
}
