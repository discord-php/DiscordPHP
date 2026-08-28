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

use Discord\Builders\CommandBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;

final class CommandNameTest extends DiscordTestCase
{
    public function testChatInputNamesAcceptDiscordCharacters(): void
    {
        $discord = $this->createMock(Discord::class);

        foreach (['foo-bar_123', 'fooʼbar', 'कमान्ड', 'คำสั่ง'] as $name) {
            $this->assertSame($name, CommandBuilder::new()->setName($name)->jsonSerialize()['name']);

            $option = new Option($discord);
            $this->assertSame($name, $option->setName($name)->name);
        }
    }

    public function testChatInputNamesRejectInvalidCharacters(): void
    {
        $this->expectException(\DomainException::class);
        CommandBuilder::new()->setName('foo.bar');
    }

    public function testOptionNamesRejectInvalidCharacters(): void
    {
        $discord = $this->createMock(Discord::class);

        $this->expectException(\DomainException::class);
        (new Option($discord))->setName('foo.bar');
    }
}
