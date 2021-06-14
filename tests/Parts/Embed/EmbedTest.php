<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Image;
use Discord\Parts\Embed\Video;

use function Discord\contains;

final class EmbedTest extends DiscordTestCase
{
    public function testCanGetVideoEmbed()
    {
        return wait(function (Discord $discord, $resolve) {
            // kek
            $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
            $this->channel()->sendMessage($url)
                ->then(function (Message $message) {
                    // fetch message to ensure embed is present
                    return $this->channel()->messages->fetch($message->id);
                })
                ->then(function (Message $message) use ($url) {
                    $this->assertEquals(1, $message->embeds->count());
                    /** @var \Discord\Parts\Embed\Embed */
                    $embed = $message->embeds->first();

                    $this->assertInstanceOf(Video::class, $embed->video);
                    $this->assertInstanceOf(Image::class, $embed->thumbnail);
                    $this->assertInstanceOf(Author::class, $embed->author);

                    $this->assertTrue(contains($embed->video->url, ['dQw4w9WgXcQ']));

                    $this->assertEquals($url, $embed->url);
                    $this->assertEquals(Embed::TYPE_VIDEO, $embed->type);
                })
                ->done($resolve, $resolve);
        }, 10);
    }

    public function testCanGetImageEmbed()
    {
        return wait(function (Discord $discord, $resolve) {
            $url = 'https://discord.com/assets/94db9c3c1eba8a38a1fcf4f223294185.png';
            $this->channel()->sendMessage($url)
                ->then(function (Message $message) {
                    // fetch message to ensure embed is present
                    return $this->channel()->messages->fetch($message->id);
                })
                ->then(function (Message $message) use ($url) {
                    $this->assertEquals(1, $message->embeds->count());
                    /** @var \Discord\Parts\Embed\Embed */
                    $embed = $message->embeds->first();

                    $this->assertEquals($url, $embed->url);
                    $this->assertEquals(Embed::TYPE_IMAGE, $embed->type);
                    $this->assertInstanceOf(Image::class, $embed->thumbnail);
                })
                ->done($resolve, $resolve);
        }, 10);
    }
}
