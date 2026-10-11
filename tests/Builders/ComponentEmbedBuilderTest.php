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

use Discord\Builders\ComponentEmbedBuilder;
use Discord\Builders\Components\Container;
use Discord\Builders\Components\TextDisplay;
use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message\Embed\Embed;

final class ComponentEmbedBuilderTest extends DiscordTestCase
{
    private static function root(array $children): array
    {
        return ['type' => 17, 'components' => $children];
    }

    private static function text(string $content = 'Preview'): array
    {
        return ['type' => 10, 'content' => $content];
    }

    private static function gallery(int $count): array
    {
        return ['type' => 12, 'items' => array_fill(0, $count, ['media' => ['url' => 'https://example.com/image.png']])];
    }

    public function testBuilderSnapshotAndScriptEncodingPreserveTextWithoutHtmlBreakout(): void
    {
        $text = TextDisplay::new('</ScRiPt><script>alert("x")</script> & 😀');
        $container = Container::new()->addComponent($text);
        $preview = ComponentEmbedBuilder::new($container);
        $original = $preview->jsonSerialize();
        $container->addComponent(TextDisplay::new('Later mutation'));
        $this->assertSame($original, $preview->jsonSerialize());
        $this->assertSame($original, json_decode($preview->toJson(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('<', $preview->toJson());
        $this->assertStringContainsString('\\u003C/ScRiPt\\u003E', $preview->toJson());
        $this->assertSame(1, substr_count(strtolower($preview->toScript()), '</script>'));
        $this->assertStringStartsWith('<script id="discord:component-embed" type="application/json">', $preview->toScript());
    }

    public function testExactly3000BytesFitAnd3001BytesFail(): void
    {
        $overhead = strlen(ComponentEmbedBuilder::new(self::root([self::text('x')]))->toJson()) - 1;
        $this->assertSame(3000, strlen(ComponentEmbedBuilder::new(self::root([self::text(str_repeat('a', 3000 - $overhead))]))->toJson()));
        $this->expectException(\LengthException::class);
        ComponentEmbedBuilder::new(self::root([self::text(str_repeat('a', 3001 - $overhead))]));
    }

    public function testUtf8BytesAndHtmlEscapesCountAtTheirEncodedLength(): void
    {
        $overhead = strlen(ComponentEmbedBuilder::new(self::root([self::text('x')]))->toJson()) - 1;
        foreach (['😀', '<'] as $character) {
            $unit = strlen(json_encode($character, JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)) - 2;
            $fits = intdiv(3000 - $overhead, $unit);
            $this->assertLessThanOrEqual(3000, strlen(ComponentEmbedBuilder::new(self::root([self::text(str_repeat($character, $fits))]))->toJson()));
            try {
                ComponentEmbedBuilder::new(self::root([self::text(str_repeat($character, $fits + 1))]));
                $this->fail('Encoded byte overflow was accepted.');
            } catch (\LengthException $e) {
                $this->assertStringContainsString('UTF-8 bytes', $e->getMessage());
            }
        }
    }

    public function testFortyComponentsIncludeTheRootAndSectionAccessories(): void
    {
        $section = ['type' => 9, 'components' => [self::text('x')], 'accessory' => ['type' => 2, 'style' => 5, 'label' => 'Go', 'url' => 'https://example.com/']];
        $children = array_merge(array_fill(0, 12, $section), array_fill(0, 3, ['type' => 14]));
        $this->assertCount(15, ComponentEmbedBuilder::new(self::root($children))->getComponent()['components']);
        $children[] = ['type' => 14];
        $this->expectException(\LengthException::class);
        ComponentEmbedBuilder::new(self::root($children));
    }

    public function testGalleryLimitIsGlobalAndThumbnailsDoNotCount(): void
    {
        $section = ['type' => 9, 'components' => [self::text()], 'accessory' => ['type' => 11, 'media' => ['url' => 'https://example.com/thumb.webp']]];
        $preview = ComponentEmbedBuilder::new(self::root([self::gallery(5), self::gallery(5), $section]));
        $this->assertCount(3, $preview->getComponent()['components']);
        $this->expectException(\LengthException::class);
        ComponentEmbedBuilder::new(self::root([self::gallery(6), self::gallery(5)]));
    }

    public function testDisplayComponentsAndEmojiOnlyLinkButtonAreAccepted(): void
    {
        $button = ['type' => 2, 'style' => 5, 'url' => 'https://example.com/', 'emoji' => ['name' => '🔗']];
        $preview = ComponentEmbedBuilder::new(self::root([self::text(), ['type' => 14], ['type' => 1, 'components' => [$button]]]));
        $this->assertSame($button, $preview->getComponent()['components'][2]['components'][0]);
    }

    public function testButtonUrlsAllow512CharactersAndReject513(): void
    {
        $prefix = 'https://example.com/';
        $url = $prefix.str_repeat('a', 512 - strlen($prefix));
        $button = ['type' => 2, 'style' => 5, 'label' => 'Go', 'url' => $url];
        $this->assertSame($url, ComponentEmbedBuilder::new(self::root([['type' => 1, 'components' => [$button]]]))->getComponent()['components'][0]['components'][0]['url']);
        $button['url'] .= 'a';
        $this->expectException(\InvalidArgumentException::class);
        ComponentEmbedBuilder::new(self::root([['type' => 1, 'components' => [$button]]]));
    }

    public function testMediaUrlsAllow2048CharactersAndReject2049(): void
    {
        $prefix = 'https://example.com/';
        $url = $prefix.str_repeat('a', 2048 - strlen($prefix));
        $gallery = ['type' => 12, 'items' => [['media' => ['url' => $url]]]];
        $this->assertSame($url, ComponentEmbedBuilder::new(self::root([$gallery]))->getComponent()['components'][0]['items'][0]['media']['url']);
        $gallery['items'][0]['media']['url'] .= 'a';
        $this->expectException(\InvalidArgumentException::class);
        ComponentEmbedBuilder::new(self::root([$gallery]));
    }

    public function testMediaFormatGuardsDistinguishGalleryVideoFromThumbnailImages(): void
    {
        $gallery = ['type' => 12, 'items' => [['media' => ['url' => 'https://example.com/clip.mp4']]]];
        $this->assertCount(1, ComponentEmbedBuilder::new(self::root([$gallery]))->getComponent()['components']);
        foreach (['https://example.com/file.pdf', 'https://example.com/file.svg', 'https://example.com/clip.mp4'] as $url) {
            $section = ['type' => 9, 'components' => [self::text()], 'accessory' => ['type' => 11, 'media' => ['url' => $url]]];
            try {
                ComponentEmbedBuilder::new(self::root([$section]));
                $this->fail('Unsupported thumbnail format accepted.');
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('media extension', $e->getMessage());
            }
        }
    }

    public function testInvalidPreviewPayloadsAreRejected(): void
    {
        $link = ['type' => 2, 'style' => 5, 'url' => 'https://example.com/', 'label' => 'Go'];
        $invalid = [
            ['type' => 10, 'content' => 'Not a root container'],
            self::root([self::root([self::text()])]),
            self::root([['type' => 13, 'file' => ['url' => 'attachment://file.txt']]]),
            self::root([['type' => 3, 'custom_id' => 'select']]),
            self::root([['type' => 1, 'components' => [array_replace($link, ['style' => 1])]]]),
            self::root([['type' => 1, 'components' => [$link + ['custom_id' => 'interaction']]]]),
            self::root([['type' => 1, 'components' => [$link + ['sku_id' => '10']]]]),
            self::root([['type' => 1, 'components' => [array_diff_key($link, ['label' => true])]]]),
            self::root([['type' => 1, 'components' => [array_replace($link, ['url' => 'javascript:alert(1)'])]]]),
            self::root([['type' => 12, 'items' => [['media' => ['url' => 'attachment://file.png']]]]]),
            self::root([['type' => 12, 'items' => [['media' => ['url' => 'https://example.com/brand.svg']]]]]),
            self::root([['type' => 12, 'items' => [['media' => ['url' => 'https://example.com/a.png', 'proxy_url' => 'received']]]]]),
            self::root([['type' => 9, 'components' => [self::text()], 'accessory' => self::text()]]),
            self::root([]),
            self::root([['type' => 14, 'spacing' => 3]]),
            self::root([self::text()]) + ['extra' => true],
        ];
        foreach ($invalid as $index => $component) {
            try {
                ComponentEmbedBuilder::new($component);
                $this->fail('Invalid preview accepted at fixture '.$index);
            } catch (\InvalidArgumentException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function testInvalidUtf8IsRejectedBeforeRenderingHtml(): void
    {
        $this->expectException(\JsonException::class);
        ComponentEmbedBuilder::new(self::root([self::text("\xFF")]));
    }

    public function testRawComponentEmbedCannotBeSentByMessageBuilder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        MessageBuilder::new()->addEmbed(['type' => 'components', 'components' => [self::root([self::text()])]]);
    }

    public function testRichBotEmbedsStillSerialize(): void
    {
        $embed = ['title' => 'Bot embed', 'description' => 'Allowed rich embed'];
        $this->assertSame([$embed], MessageBuilder::new()->addEmbed($embed)->jsonSerialize()['embeds']);
        $part = getMockDiscord()->getFactory()->part(Embed::class, $embed);
        $this->assertArrayNotHasKey('components', $part->jsonSerialize());
        $serialized = $part->jsonSerialize();
        $this->assertSame([$serialized], MessageBuilder::new()->addEmbed($serialized)->jsonSerialize()['embeds']);
    }

    public function testFailedReplacementPreservesTheLastValidPreview(): void
    {
        $preview = ComponentEmbedBuilder::new(self::root([self::text()]));
        $before = $preview->toJson();
        try {
            $preview->setComponent(self::root([self::root([self::text()])]));
            $this->fail('Nested Container accepted.');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame($before, $preview->toJson());
        }
    }

    public function testMissingRootCannotSerialize(): void
    {
        $this->expectException(\LogicException::class);
        (new ComponentEmbedBuilder())->toScript();
    }

    public function testReceivedPartsMustBeReauthoredForWebsiteOutput(): void
    {
        $this->expectException(\BadMethodCallException::class);
        ComponentEmbedBuilder::fromPart(getMockDiscord()->getFactory()->part(Embed::class, ['title' => 'Received']));
    }
}
