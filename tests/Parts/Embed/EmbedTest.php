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
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Thumbnail;
use Discord\Parts\Embed\Video;

use function Discord\contains;

it('can retrieve a video embed', function () {
    return wait(function (Discord $discord, $resolve) {
        $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
        $this->channel()->sendMessage($url)
            ->then(fn (Message $message) => $this->channel()->messages->fetch($message->id))
            ->then(function (Message $message) use ($url) {
                expect($message->embeds->count())->toBe(1);

                /** @var Embed */
                $embed = $message->embeds->first();

                expect($embed->video)->toBeInstanceOf(Video::class);
                expect($embed->thumbnail)->toBeInstanceOf(Thumbnail::class);
                expect($embed->author)->toBeInstanceOf(Author::class);
                expect(contains($embed->video->url, ['dQw4w9WgXcQ']))->toBeTrue();
                expect($embed->url)->toBe($url);
                expect($embed->type)->toBe(Embed::TYPE_VIDEO);
            })
            ->then($resolve, $resolve);
    }, 10);
});

it('can retrieve an image embed', function () {
    return wait(function (Discord $discord, $resolve) {
        $url = 'https://discord.com/assets/94db9c3c1eba8a38a1fcf4f223294185.png';
        $this->channel()->sendMessage($url)
            ->then(fn (Message $message) => $this->channel()->messages->fetch($message->id))
            ->then(function (Message $message) use ($url) {
                expect($message->embeds->count())->toBe(1);

                /** @var Embed */
                $embed = $message->embeds->first();

                expect($embed->url)->toBe($url);
                expect($embed->type)->toBe(Embed::TYPE_IMAGE);
                expect($embed->thumbnail)->toBeInstanceOf(Thumbnail::class);
            })
            ->then($resolve, $resolve);
    }, 10);
});

