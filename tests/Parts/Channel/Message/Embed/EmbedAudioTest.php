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
use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message\Embed\Audio;
use Discord\Parts\Channel\Message\Embed\Embed;
use Discord\Parts\Channel\Message\Message;
use Discord\Repository\Channel\MessageRepository;

final class EmbedAudioTest extends DiscordTestCase
{
    private function fixture(): array
    {
        return [
            'id' => '100',
            'channel_id' => '20',
            'embeds' => [[
                'type' => 'rich',
                'audio' => [
                    'url' => 'https://example.com/clip.mp3',
                    'proxy_url' => 'https://media.example.com/clip.mp3',
                    'content_type' => 'audio/mpeg',
                    'description' => 'A short recording',
                ],
            ]],
        ];
    }

    public function testReceivedMessageHydratesAndRetainsAudio(): void
    {
        $message = getMockDiscord()->getFactory()->part(Message::class, $this->fixture(), true);
        $embed = $message->embeds->first();
        $audio = $embed->audio;

        $this->assertInstanceOf(Audio::class, $audio);
        $this->assertSame($this->fixture()['embeds'][0]['audio'], $audio->getRawAttributes());
        $this->assertSame($audio->getRawAttributes(), json_decode(json_encode($audio, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame($audio, $embed->audio);
        $this->assertTrue($audio->created);
        $embed->created = false;
        $this->assertFalse($audio->created);
    }

    public function testAbsentNullAndRemovedAudioAreNull(): void
    {
        $factory = getMockDiscord()->getFactory();
        foreach ([[], ['audio' => null]] as $attributes) {
            $this->assertNull($factory->part(Embed::class, $attributes)->audio);
        }

        $embed = $factory->part(Embed::class, $this->fixture()['embeds'][0], true);
        $this->assertInstanceOf(Audio::class, $embed->audio);
        $embed->fill(['audio' => null]);
        $this->assertNull($embed->audio);
    }

    public function testRestFetchHydratesAudio()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => $this->fixture());
            $mock->getHttpClient()->setDriver($driver);
            $repository = $mock->getFactory()->repository(MessageRepository::class, ['channel_id' => '20']);

            $discord->getLoop()->futureTick(function () use ($repository, $driver, $resolve) {
                $repository->fetch('100')->then(function (Message $message) use ($driver) {
                    $this->assertInstanceOf(Audio::class, $message->embeds->first()->audio);
                    $this->assertSame('audio/mpeg', $message->embeds->first()->audio->content_type);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/channels/20/messages/100', $driver->requests[0]['url']);
                })->then($resolve, $resolve);
            });
        });
    }

    public function testDraftEmbedAudioSurvivesMessageBuilderSerialization(): void
    {
        $audio = ['url' => 'https://example.com/clip.mp3', 'description' => 'A short recording'];
        $embed = getMockDiscord()->getFactory()->part(Embed::class, ['audio' => $audio]);
        $this->assertInstanceOf(Audio::class, $embed->audio);
        $body = json_decode(json_encode(MessageBuilder::new()->addEmbed($embed), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($audio, $body['embeds'][0]['audio']);
        $this->assertArrayNotHasKey('proxy_url', $body['embeds'][0]['audio']);
        $this->assertArrayNotHasKey('content_type', $body['embeds'][0]['audio']);
    }

    public function testNullableDraftFieldsArePreserved(): void
    {
        $audio = getMockDiscord()->getFactory()->part(Audio::class, ['url' => null, 'description' => null]);

        $this->assertSame(['url' => null, 'description' => null], $audio->jsonSerialize());
    }

    public function testEditingReceivedEmbedOmitsResponseMetadataWithoutLosingIt(): void
    {
        $embed = getMockDiscord()->getFactory()->part(Embed::class, $this->fixture()['embeds'][0], true);
        $audio = $embed->audio;
        $body = json_decode(json_encode(MessageBuilder::new()->addEmbed($embed), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['url' => $audio->url, 'description' => $audio->description], $body['embeds'][0]['audio']);
        $this->assertSame('audio/mpeg', $audio->content_type);
        $this->assertSame('https://media.example.com/clip.mp3', $audio->proxy_url);
    }

    public function testSendRequestContainsOnlyAuthoredAudioFields()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => $this->fixture());
            $mock->getHttpClient()->setDriver($driver);
            $channel = $mock->getFactory()->part(Channel::class, ['id' => '20', 'type' => Channel::TYPE_DM], true);
            $embed = $mock->getFactory()->part(Embed::class, $this->fixture()['embeds'][0], true);

            $discord->getLoop()->futureTick(function () use ($channel, $embed, $driver, $resolve) {
                $channel->sendMessage(MessageBuilder::new()->addEmbed($embed))->then(function (Message $message) use ($driver) {
                    $request = $driver->requests[0];
                    $this->assertSame('POST', $request['method']);
                    $this->assertStringEndsWith('/channels/20/messages', $request['url']);
                    $this->assertSame(1, preg_match('/name=payload_json\r?\nContent-Type: application\/json\r?\n\r?\n([^\r\n]+)/', $request['raw'], $matches));
                    $payload = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
                    $this->assertSame(['url' => 'https://example.com/clip.mp3', 'description' => 'A short recording'], $payload['embeds'][0]['audio']);
                    $this->assertSame('audio/mpeg', $message->embeds->first()->audio->content_type);
                })->then($resolve, $resolve);
            });
        });
    }
}
