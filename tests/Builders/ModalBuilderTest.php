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

use Discord\Builders\Components\Label;
use Discord\Builders\ModalBuilder;
use PHPUnit\Framework\TestCase;

final class ModalBuilderTest extends TestCase
{
    public function testNew()
    {
        $label = new Label();

        $builder = ModalBuilder::new('title', 'custom_id', [$label]);

        $this->assertSame('title', $builder->getTitle());
        $this->assertSame('custom_id', $builder->getCustomId());
        $this->assertSame([$label], $builder->getComponents());
    }

    public function testAddComponent()
    {
        $label = new Label();

        $builder = new ModalBuilder();
        $builder->addComponent($label);

        $this->assertSame([$label], $builder->getComponents());
    }

    public function testSetComponents()
    {
        $label = new Label();

        $builder = new ModalBuilder();
        $builder->setComponents([$label]);

        $this->assertSame([$label], $builder->getComponents());
    }

    public function testSetTooLongTitleThrows()
    {
        $builder = new ModalBuilder();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Modal title can not be longer than 45 characters');

        $builder->setTitle(str_repeat('a', 101));
    }

    public function testSetTooLongCustomIdThrows()
    {
        $builder = new ModalBuilder();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Custom ID must be maximum 100 characters.');

        $builder->setCustomId(str_repeat('a', 101));
    }

    public function testAddComponentThrowsAfterReachingLimit()
    {
        $builder = new ModalBuilder();
        $builder->setComponents([new Label(), new Label(), new Label(), new Label(), new Label()]);

        $this->expectException(\OverflowException::class);
        $this->expectExceptionMessage('You can only have 5 components per modal.');

        $builder->addComponent(new Label());
    }
}
