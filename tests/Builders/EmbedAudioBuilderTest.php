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

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message\Message;
use PHPUnit\Framework\Attributes\DataProvider;

final class EmbedAudioBuilderTest extends DiscordTestCase
{
    public function testCharacterLimitsAndExplicitNulls(): void
    {
        $audio = ['url' => 'https://example.com/'.str_repeat('a', 2028), 'description' => str_repeat('é', 4096)];
        $this->assertSame($audio, MessageBuilder::new()->addEmbed(['audio' => $audio])->jsonSerialize()['embeds'][0]['audio']);

        $audio = ['url' => null, 'description' => null];
        $this->assertSame($audio, MessageBuilder::new()->addEmbed(['audio' => $audio])->jsonSerialize()['embeds'][0]['audio']);
        $this->assertNull(MessageBuilder::new()->addEmbed(['audio' => null])->jsonSerialize()['embeds'][0]['audio']);
        $this->assertArrayNotHasKey('audio', MessageBuilder::new()->addEmbed(['title' => 'No audio'])->jsonSerialize()['embeds'][0]);
    }

    public function testEmptyAudioIsAnObjectInJson(): void
    {
        $body = json_decode(json_encode(MessageBuilder::new()->addEmbed(['audio' => []]), JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
        $this->assertInstanceOf(stdClass::class, $body->embeds[0]->audio);
    }

    public static function invalidAudio(): iterable
    {
        yield 'scalar audio' => ['audio', InvalidArgumentException::class];
        yield 'list audio' => [['https://example.com/clip.mp3'], InvalidArgumentException::class];
        yield 'non-string URL' => [['url' => 10], InvalidArgumentException::class];
        yield 'non-string description' => [['description' => false], InvalidArgumentException::class];
        yield 'long URL' => [['url' => 'https://example.com/'.str_repeat('a', 2029)], LengthException::class];
        yield 'long description' => [['description' => str_repeat('é', 4097)], LengthException::class];
    }

    #[DataProvider('invalidAudio')]
    public function testInvalidAudioIsRejected($audio, string $exception): void
    {
        $this->expectException($exception);
        MessageBuilder::new()->addEmbed(['audio' => $audio]);
    }

    public function testFromPartIsValidatedAtSerialization(): void
    {
        $message = getMockDiscord()->getFactory()->part(Message::class, ['embeds' => [['audio' => ['description' => str_repeat('a', 4097)]]]], true);

        $this->expectException(LengthException::class);
        MessageBuilder::fromPart($message)->jsonSerialize();
    }

    public function testMutatingAnAudioObjectAfterAddingDoesNotChangeThePayload(): void
    {
        $audio = (object) ['url' => 'https://example.com/clip.mp3'];
        $builder = MessageBuilder::new()->addEmbed(['audio' => $audio]);
        $audio->url = str_repeat('a', 2049);

        $this->assertSame('https://example.com/clip.mp3', $builder->jsonSerialize()['embeds'][0]['audio']['url']);
    }
}
