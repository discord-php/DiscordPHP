<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Builders\Components\Label;
use Discord\Builders\ModalBuilder;
use PHPUnit\Framework\TestCase;

final class ModalBuilderTest extends TestCase
{
    public function testAddComponent()
    {
        $label = new Label();

        $builder = new ModalBuilder();
        $builder->addComponent($label);

        $this->assertSame([$label], $builder->getComponents());
    }
}
