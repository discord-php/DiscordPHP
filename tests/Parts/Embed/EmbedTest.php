<?php

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
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Thumbnail;
use Discord\Parts\Embed\Video;
use Discord\WebSockets\Event;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

use function Discord\contains;

final class EmbedTest extends DiscordTestCase
{
    public function testCanGetVideoEmbed()
    {
        // kek
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        /** @var Message|false $message */
        $message = wait(function (Discord $discord, $resolve) use ($url) {
            $this->sendAndAwaitEmbed($discord, $url)->then($resolve, $resolve);
        }, 30, static fn () => null);

        if (! $message instanceof Message || $message->embeds->count() === 0) {
            $this->markTestSkipped('Discord did not unfurl the URL into an embed within the timeout.');
        }

        $this->assertEquals(1, $message->embeds->count());
        /** @var Embed $embed */
        $embed = $message->embeds->first();

        $this->assertInstanceOf(Video::class, $embed->video);
        $this->assertInstanceOf(Thumbnail::class, $embed->thumbnail);
        $this->assertInstanceOf(Author::class, $embed->author);

        $this->assertTrue(contains($embed->video->url, ['dQw4w9WgXcQ']));

        $this->assertEquals($url, $embed->url);
        $this->assertEquals(Embed::TYPE_VIDEO, $embed->type);
    }

    public function testCanGetImageEmbed()
    {
        $url = 'https://discord.com/assets/94db9c3c1eba8a38a1fcf4f223294185.png';

        /** @var Message|false $message */
        $message = wait(function (Discord $discord, $resolve) use ($url) {
            $this->sendAndAwaitEmbed($discord, $url)->then($resolve, $resolve);
        }, 30, static fn () => null);

        if (! $message instanceof Message || $message->embeds->count() === 0) {
            $this->markTestSkipped('Discord did not unfurl the URL into an embed within the timeout.');
        }

        $this->assertEquals(1, $message->embeds->count());
        /** @var Embed $embed */
        $embed = $message->embeds->first();

        $this->assertEquals($url, $embed->url);
        $this->assertEquals(Embed::TYPE_IMAGE, $embed->type);
        $this->assertInstanceOf(Thumbnail::class, $embed->thumbnail);
    }

    /**
     * Posts $url to the test channel and resolves once Discord has attached an
     * embed to the message.
     *
     * Discord unfurls links asynchronously and delivers the embed on a later
     * MESSAGE_UPDATE, sometimes seconds after the message is created, so asserting
     * against the message straight after sendMessage() races the unfurl. This
     * waits for the MESSAGE_UPDATE for exactly this message id, then detaches its
     * listener. If the unfurl never arrives it resolves with the embed-less
     * message after $timeout so the caller can skip.
     *
     * @return PromiseInterface<Message>
     */
    private function sendAndAwaitEmbed(Discord $discord, string $url, float $timeout = 25.0): PromiseInterface
    {
        return $this->channel()->sendMessage($url)->then(function (Message $sent) use ($discord, $timeout) {
            // Occasionally Discord has already unfurled by the time the send resolves.
            if ($sent->embeds->count() > 0) {
                return $sent;
            }

            $deferred = new Deferred();
            $listener = null;
            $timer = null;

            $finish = function (Message $result) use ($discord, &$listener, &$timer, $deferred): void {
                if ($listener !== null) {
                    $discord->removeListener(Event::MESSAGE_UPDATE, $listener);
                    $listener = null;
                }
                if ($timer !== null) {
                    $discord->getLoop()->cancelTimer($timer);
                    $timer = null;
                }

                $deferred->resolve($result);
            };

            $listener = function ($message) use ($sent, $finish): void {
                if (
                    $message instanceof Message
                    && (string) $message->id === (string) $sent->id
                    && $message->embeds->count() > 0
                ) {
                    $finish($message);
                }
            };

            $discord->on(Event::MESSAGE_UPDATE, $listener);
            $timer = $discord->getLoop()->addTimer($timeout, static fn () => $finish($sent));

            return $deferred->promise();
        });
    }
}
