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
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Footer;

use function Discord\getColor;

it('can send a rich embed', function () {
    return wait(function (Discord $discord, $resolve) {
        $embed = new Embed($discord);
        $embed->setTitle('Testing Embed')
            ->setAuthor('DiscordPHP Bot')
            ->setDescription('Embed Description')
            ->setColor(getColor('lightblue'))
            ->addField(['name' => 'Field 1', 'value' => 'Value 1', 'inline' => true])
            ->addField(['name' => 'Field 2', 'value' => 'Value 2', 'inline' => false])
            ->setFooter('Footer Value');

        $this->channel()->sendMessage(MessageBuilder::new()->addEmbed($embed))
            ->then(function (Message $message) use ($resolve) {
                expect($message->embeds->count())->toBe(1);

                /** @var Embed */
                $embed = $message->embeds->first();
                expect($embed->title)->toBe('Testing Embed');
                expect($embed->type)->toBe(Embed::TYPE_RICH);
                expect($embed->description)->toBe('Embed Description');
                expect($embed->color)->toBe(getColor('lightblue'));
                expect($embed->author)->toBeInstanceOf(Author::class);
                expect($embed->author->name)->toBe('DiscordPHP Bot');
                expect($embed->footer)->toBeInstanceOf(Footer::class);
                expect($embed->footer->text)->toBe('Footer Value');
                expect($embed->fields->count())->toBe(2);
                expect($embed->fields->get('name', 'Field 1'))->not->toBeNull();
                expect($embed->fields->get('name', 'Field 2'))->not->toBeNull();
                expect((string) $embed->fields->get('name', 'Field 1'))->not->toBe((string) $embed->fields->get('name', 'Field 2'));
            })
            ->then($resolve, $resolve);
    }, 10);
});

