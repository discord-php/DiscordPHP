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

use Discord\Parts\Channel\Message\ContentScanMetadata;
use Discord\Parts\Channel\Message\UnfurledMediaItem;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\Channel\Message\Attachment;
use Discord\Parts\Channel\Message\Embed\Embed;

final class ContentScanTest extends DiscordTestCase
{
    public function testUnfurledMediaRetainsOptionalAndNullableResponseFields(): void
    {
        $media = getMockDiscord()->getFactory()->part(UnfurledMediaItem::class, [
            'url' => 'https://example.com/image.png',
            'placeholder' => 'thumbhash',
            'placeholder_version' => 1,
            'flags' => 1,
        ], true);
        $this->assertNull($media->proxy_url);
        $this->assertSame('thumbhash', $media->placeholder);
        $this->assertSame(1, $media->placeholder_version);
        $this->assertSame(UnfurledMediaItem::FLAG_IS_ANIMATED, $media->flags);

        $media->fill(['placeholder' => null, 'placeholder_version' => null, 'flags' => 0]);
        $this->assertNull($media->placeholder);
        $this->assertNull($media->placeholder_version);
        $this->assertSame(0, $media->flags);
        $this->assertSame('https://example.com/image.png', $media->url);
    }

    public function testScanMetadataSurvivesHydrationAndLateReplacement(): void
    {
        $factory = getMockDiscord()->getFactory();
        $media = $factory->part(UnfurledMediaItem::class, ['url' => 'https://example.com/image.png'], true);
        $this->assertNull($media->content_scan_metadata);
        foreach ([0, 1, 2, 4, 7, 263] as $flags) {
            $media->fill(['content_scan_metadata' => ['flags' => $flags]]);
            $scan = $media->content_scan_metadata;
            $this->assertInstanceOf(ContentScanMetadata::class, $scan);
            $this->assertSame($flags, $scan->flags);
            $this->assertSame($scan, $media->content_scan_metadata);
            $this->assertTrue($scan->created);
            $this->assertSame(['flags' => $flags], json_decode(json_encode($scan, JSON_THROW_ON_ERROR), true));
        }
        $media->created = false;
        $this->assertFalse($media->content_scan_metadata->created);
        $media->fill(['content_scan_metadata' => null]);
        $this->assertNull($media->content_scan_metadata);
    }

    public function testLateMessagePayloadExposesNestedComponentScanResults(): void
    {
        $component = ['type' => 12, 'items' => [['media' => ['url' => 'https://example.com/image.png']]]];
        $message = getMockDiscord()->getFactory()->part(Message::class, ['id' => '100', 'channel_id' => '20', 'components' => [$component]], true);
        $this->assertNull($message->components->first()->items->first()->media->content_scan_metadata);
        $component['items'][0]['media']['content_scan_metadata'] = ['flags' => 7];
        $message->fill(['components' => [$component]]);
        $this->assertSame(7, $message->components->first()->items->first()->media->content_scan_metadata->flags);
        $message->fill(['content' => 'A separate partial update']);
        $this->assertSame(7, $message->components->first()->items->first()->media->content_scan_metadata->flags);
    }

    public function testRestFetchRetainsComponentScanMetadata()
    {
        return wait(function (\Discord\Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => ['id' => '100', 'channel_id' => '20', 'components' => [
                ['type' => 12, 'items' => [['media' => ['url' => 'https://example.com/image.png', 'content_scan_metadata' => ['flags' => 5]]]]],
            ]]);
            $mock->getHttpClient()->setDriver($driver);
            $repository = $mock->getFactory()->repository(\Discord\Repository\Channel\MessageRepository::class, ['channel_id' => '20']);
            $discord->getLoop()->futureTick(function () use ($repository, $driver, $resolve) {
                $repository->fetch('100')->then(function (Message $message) use ($driver) {
                    $this->assertSame(5, $message->components->first()->items->first()->media->content_scan_metadata->flags);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/channels/20/messages/100', $driver->requests[0]['url']);
                })->then($resolve, $resolve);
            });
        });
    }

    public function testAttachmentAndEmbedFlagsRemainIndependentOfScanMetadataFlags(): void
    {
        $factory = getMockDiscord()->getFactory();
        foreach ([Attachment::class, Embed::class] as $class) {
            $flags = (1 << 4) | (1 << 6) | (1 << 7) | (1 << 12);
            $part = $factory->part($class, ['flags' => $flags]);
            $this->assertSame($flags, $part->flags);
            $this->assertSame(16, $class::FLAG_CONTAINS_EXPLICIT_CONTENT);
            $this->assertSame(64, $class::FLAG_CONTAINS_GORE_CONTENT);
            $this->assertSame(128, $class::FLAG_CONTAINS_SELF_HARM_CONTENT);
        }
        $this->assertSame(1, ContentScanMetadata::FLAG_IS_EXPLICIT_CONTENT);
        $this->assertSame(2, ContentScanMetadata::FLAG_IS_GORE_CONTENT);
        $this->assertSame(4, ContentScanMetadata::FLAG_IS_SELF_HARM_CONTENT);
    }
}
