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

use Discord\Parts\Application\Entitlement;
use Discord\Discord;
use Discord\Parts\Channel\Message\Component;
use Discord\Parts\Channel\Message\FileUpload;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\ScheduledEvent\ScheduledEventException;
use Discord\Parts\SKU\Subscription;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\MessageInteraction;
use Discord\Parts\WebSockets\PresenceUpdate;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;

final class MagicAttributeCasesTest extends DiscordTestCase
{
    public static function relations(): iterable
    {
        yield 'entitlement guild' => [Entitlement::class, 'guild', Guild::class, 'guilds'];
        yield 'entitlement user' => [Entitlement::class, 'user', User::class, 'users'];
        yield 'subscription user' => [Subscription::class, 'user', User::class, 'users'];
        yield 'scheduled event exception guild' => [ScheduledEventException::class, 'guild', Guild::class, 'guilds'];
        yield 'message interaction guild' => [MessageInteraction::class, 'guild', Guild::class, 'guilds'];
    }

    #[DataProvider('relations')]
    public function testRelationsFollowTheCacheWithoutFetching(string $class, string $relation, string $relatedClass, string $repository): void
    {
        $discord = $this->discord();
        $driver = getMockHttpDriver(function () {
            throw new \LogicException('Attribute access must not fetch.');
        });
        $discord->getHttpClient()->setDriver($driver);
        $factory = $discord->getFactory();
        foreach ([[], [$relation.'_id' => null], [$relation.'_id' => '10']] as $attributes) {
            $part = $factory->part($class, $attributes, true);
            $this->assertNull($part->{$relation});
            $this->assertFalse(isset($part->{$relation}));
            $this->assertFalse(isset($part[$relation]));
        }

        $related = $factory->part($relatedClass, ['id' => '10'], true);
        $discord->{$repository}->pushItem($related);
        $this->assertSame($related, $part->{$relation});
        $this->assertSame($related, $part[$relation] ?? null);
        $this->assertTrue(isset($part->{$relation}));
        $this->assertFalse(empty($part->{$relation}));
        $this->assertArrayNotHasKey($relation, $part->getRawAttributes());
        $this->assertArrayNotHasKey($relation, $part->__serialize());

        $replacement = $factory->part($relatedClass, ['id' => '10'], true);
        $discord->{$repository}->pushItem($replacement);
        $this->assertSame($replacement, $part->{$relation}, 'Relations use the current cache identity, not a hydrated snapshot.');
        $this->assertSame([$relation.'_id' => '10'], $part->__serialize());

        foreach ([[], [$relation.'_id' => null]] as $attributes) {
            $withoutId = $factory->part($class, $attributes, true);
            $this->assertNull($withoutId->{$relation}, 'A populated cache must not resolve an absent ID.');
        }

        unset($discord->{$repository}['10']);
        $this->assertNull($part->{$relation}, 'Removing the cached object must remove the resolved relation.');
        $this->assertFalse(isset($part[$relation]));

        $part->{$relation.'_id'} = '20';
        $this->assertNull($part->{$relation});
        $this->assertFalse(isset($part->{$relation}));
        $this->assertSame([], $driver->requests);
    }

    public function testEmbeddedPresenceStatusUsesTheNestedClientStatus(): void
    {
        $part = $this->discord()->getFactory()->part(PresenceUpdate::class, [
            'client_status' => (object) ['embedded' => 'idle'],
        ], true);
        $this->assertSame('idle', $part->embedded_status);
        $this->assertSame('idle', $part['embedded_status'] ?? null);
        $this->assertSame('idle', $part->{'embedded-status'});
        $this->assertSame('idle', $part->embeddedStatus);
        $this->assertTrue(isset($part->embedded_status));

        $part->client_status = (object) [];
        $this->assertNull($part->embedded_status);
        $this->assertFalse(isset($part['embedded_status']));
        unset($part['client_status']);
        $this->assertNull($part->embedded_status);
    }

    public function testFileUploadKeepsItsTypeWhenHydratedThroughTheComponentMap(): void
    {
        $payload = ['type' => 19, 'id' => 7, 'custom_id' => 'files'];
        $part = $this->discord()->getFactory()->part(Component::TYPES[$payload['type']], $payload, true);
        $this->assertInstanceOf(FileUpload::class, $part);
        $this->assertSame(19, $part->type);
        $this->assertSame(19, $part['type'] ?? null);
        $this->assertSame($payload, $part->getRawAttributes());
        $this->assertSame(19, $part->jsonSerialize()['type']);
    }

    private function discord(): Discord
    {
        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connectWs'])
            ->getMock();
        $discord->expects($this->once())->method('connectWs');
        $discord->__construct(['token' => '', 'logger' => new NullLogger(), 'socket_options' => ['dns' => false]]);

        return $discord;
    }
}
