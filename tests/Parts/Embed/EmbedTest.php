<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Video;
use Discord\WebSockets\Event;
use PHPUnit\Framework\TestCase;

use function Discord\contains;

final class EmbedTest extends TestCase
{
    /**
     * @depends DiscordTest::testCanGetChannel
     */
    public function testCanGetVideoEmbed(Channel $channel)
    {
        return wait(function (Discord $discord, $resolve) use ($channel) {
            // kek
            $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
            $handler = function (Message $message) use ($discord, $resolve, $url, &$handler) {
                // ignore messages not created by self
                if ($message->author->id != $discord->id) {
                    return;
                }
                $discord->removeListener(Event::MESSAGE_CREATE, $handler);

                $this->assertEquals(1, $message->embeds->count());
                /** @var \Discord\Parts\Embed\Embed */
                $embed = $message->embeds->first();

                $this->assertInstanceOf(Video::class, $embed->video);
                $this->assertInstanceOf(Image::class, $embed->thumbnail);
                $this->assertInstanceOf(Author::class, $embed->author);

                $this->assertTrue(contains($embed->video->url, ['dQw4w9WgXcQ']));

                $this->assertEquals($url, $embed->url);
                $this->assertEquals(Embed::TYPE_VIDEO, $embed->type);

                $resolve();
            };

            $discord->on(Event::MESSAGE_CREATE, $handler);
            $channel->sendMessage($url);
        }, 10);
    }

    /**
     * @depends DiscordTest::testCanGetChannel
     */
    public function testCanGetImageEmbed(Channel $channel)
    {
        return wait(function (Discord $discord, $resolve) use ($channel) {
            $url = 'https://discord.com/assets/94db9c3c1eba8a38a1fcf4f223294185.png';
            $handler = function (Message $message) use ($discord, $resolve, $url, &$handler) {
                // ignore messages not created by self
                if ($message->author->id != $discord->id || $message->embeds->count() < 1) {
                    return;
                }

                $this->assertEquals(1, $message->embeds->count());
                /** @var \Discord\Parts\Embed\Embed */
                $embed = $message->embeds->first();

                $this->assertEquals($url, $embed->url);
                $this->assertEquals(Embed::TYPE_IMAGE, $embed->type);
                $this->assertInstanceOf(Image::class, $embed->thumbnail);

                $resolve();
            };

            $discord->on(Event::MESSAGE_CREATE, $handler);
            $discord->on(Event::MESSAGE_UPDATE, $handler);
            $channel->sendMessage($url);

            return function () use ($discord, $handler) {
                $discord->removeListener(Event::MESSAGE_CREATE, $handler);
                $discord->removeListener(Event::MESSAGE_UPDATE, $handler);
            };
        }, 10);
    }
}
