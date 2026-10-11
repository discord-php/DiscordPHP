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
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message\Container;
use Discord\Parts\Channel\Message\Section;
use Discord\Parts\Channel\Message\Thumbnail;
use Discord\Parts\Channel\Message\MediaGallery;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\Channel\Message\MessageSnapshot;
use Discord\Parts\Channel\Message\Embed\Embed;
use Discord\Parts\Channel\Message\Embed\EmbedComponents;
use Discord\Parts\Channel\Message\Embed\EmbedRich;
use Discord\Repository\Channel\MessageRepository;
use Discord\WebSockets\Events\MessageCreate;
use Discord\WebSockets\Events\MessageUpdate;
use Discord\WebSockets\Intents;
use Psr\Log\NullLogger;

use function Discord\promiseFromGenerator;

final class ComponentEmbedTest extends DiscordTestCase
{
    private function fixture(): object
    {
        return json_decode(file_get_contents(__DIR__.'/../../../../Fixtures/component-embed-message.json'), false, 512, JSON_THROW_ON_ERROR);
    }

    private function assertPreview(Message $message): void
    {
        $embed = $message->embeds->first();
        $this->assertInstanceOf(EmbedComponents::class, $embed);
        $this->assertSame(Embed::TYPE_COMPONENTS, $embed->type);
        $this->assertCount(1, $embed->components);
        $container = $embed->components->first();
        $this->assertInstanceOf(Container::class, $container);
        $this->assertSame(5793266, $container->accent_color);
        $this->assertFalse($container->spoiler);
        $this->assertSame($message->created, $container->created);
        $section = $container->components->first();
        $this->assertInstanceOf(Section::class, $section);
        $this->assertSame('# DiscordPHP', $section->components->first()->content);
        $this->assertInstanceOf(Thumbnail::class, $section->accessory);
        $this->assertSame(256, $section->accessory->media->width);
        $gallery = $container->components->get('id', 5);
        $this->assertInstanceOf(MediaGallery::class, $gallery);
        $this->assertSame('Project preview', $gallery->items->first()->description);
        $button = $container->components->last()->components->first();
        $this->assertSame(5, $button->style);
        $this->assertSame('https://discordphp.org/', $button->url);
        $this->assertCount(0, $message->components, 'Preview components are distinct from message components.');
        $this->assertInstanceOf(EmbedRich::class, $message->embeds->last());
    }

    public function testReceivedMessageAndForwardedSnapshotHydrateThePreview(): void
    {
        $mock = getMockDiscord();
        $message = $mock->getFactory()->part(Message::class, (array) $this->fixture(), true);
        $this->assertPreview($message);
        $this->assertSame($message->embeds->first()->components->first(), $message->embeds->first()->components->first());
        $snapshot = $mock->getFactory()->part(MessageSnapshot::class, ['message' => $this->fixture()], true);
        $this->assertPreview($snapshot->message);
    }

    public function testMissingNullAndEmptyComponentsAreEmptyCollections(): void
    {
        foreach ([[], ['components' => null], ['components' => []]] as $attributes) {
            $embed = getMockDiscord()->getFactory()->part(EmbedComponents::class, ['type' => 'components'] + $attributes, true);
            $this->assertCount(0, $embed->components);
        }
    }

    public function testAssociativeFixtureArraysHydrateTheSameComponentTree(): void
    {
        $attributes = json_decode(json_encode($this->fixture(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $message = getMockDiscord()->getFactory()->part(Message::class, $attributes, true);
        $this->assertPreview($message);
    }

    public function testRestFetchUsesTheSameHydration()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => $this->fixture());
            $mock->getHttpClient()->setDriver($driver);
            $repo = $mock->getFactory()->repository(MessageRepository::class, ['channel_id' => '20']);
            $repo->fetch('100')->then(function (Message $message) use ($driver) {
                $this->assertPreview($message);
                $this->assertSame('GET', $driver->requests[0]['method']);
                $this->assertStringEndsWith('/channels/20/messages/100', $driver->requests[0]['url']);
            })->then($resolve, $resolve);
        });
    }

    public function testGatewayCreateAndPartialUpdatePreserveTypedCachedState()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = new Discord(['token' => '', 'logger' => new NullLogger(), 'storeMessages' => true, 'intents' => Intents::MESSAGE_CONTENT]);
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => null));
            promiseFromGenerator((new MessageCreate($mock))->handle($this->fixture()))
                ->then(function (Message $message) use ($mock) {
                    $this->assertPreview($message);
                    $update = $this->fixture();
                    unset($update->type, $update->author);
                    $update->embeds[0]->components[0]->accent_color = 123;

                    return promiseFromGenerator((new MessageUpdate($mock))->handle($update));
                })->then(function (array $result) use ($mock) {
                    [$message, $old] = $result;
                    $this->assertSame(123, $message->embeds->first()->components->first()->accent_color);
                    $this->assertSame(5793266, $old->embeds->first()->components->first()->accent_color);
                    $this->assertSame($message, $mock->private_channels->get('id', '20')->messages->get('id', '100'));
                })->then($resolve, $resolve);
        });
    }

    public function testReceivedPreviewCannotBeAddedToAnOutboundBotMessage(): void
    {
        $embed = getMockDiscord()->getFactory()->part(EmbedComponents::class, (array) $this->fixture()->embeds[0], true);
        $this->expectException(\InvalidArgumentException::class);
        MessageBuilder::new()->addEmbed($embed);
    }

    public function testFromPartCannotBypassTheOutboundGuard(): void
    {
        $message = getMockDiscord()->getFactory()->part(Message::class, (array) $this->fixture(), true);
        $this->expectException(\InvalidArgumentException::class);
        MessageBuilder::fromPart($message)->jsonSerialize();
    }
}
