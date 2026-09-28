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

use Discord\Builders\Components\CheckboxGroup;
use Discord\Builders\Components\GroupOption;
use Discord\Builders\Components\TextInput;
use Discord\Builders\MessageBuilder;
use Discord\Helpers\Collection;
use Discord\Helpers\Snowflake;

final class DynamicPropertyMutatorTest extends DiscordTestCase
{
    public function testAPropertyWithAGetterIsSet(): void
    {
        $snowflake = new Snowflake('175928847299117063');

        $this->assertTrue(isset($snowflake->id));
        $this->assertTrue(isset($snowflake->worker_id));
        $this->assertFalse(empty($snowflake->increment));
    }

    public function testAPropertyWithoutAGetterIsNotSet(): void
    {
        $snowflake = new Snowflake('175928847299117063');

        $this->assertFalse(isset($snowflake->not_a_property));
    }

    public function testAGetterThatYieldsNullIsNotSet(): void
    {
        $builder = MessageBuilder::new();

        $this->assertFalse(isset($builder->content));
        $this->assertTrue(empty($builder->content));

        $builder->setContent('hello');

        $this->assertTrue(isset($builder->content));
        $this->assertSame('hello', $builder->content);
    }

    public function testCollectionGetFindsBuildersByAGetterBackedProperty(): void
    {
        $components = Collection::for(TextInput::class, null);
        $components->pushItem(TextInput::new('Title', TextInput::STYLE_SHORT, 'title'));
        $components->pushItem(TextInput::new('Instructions', TextInput::STYLE_PARAGRAPH, 'instructions'));

        $found = $components->get('custom_id', 'instructions');

        $this->assertInstanceOf(TextInput::class, $found);
        $this->assertSame('instructions', $found->getCustomId());
        $this->assertNull($components->get('custom_id', 'missing'));
    }

    public function testACheckboxGroupSendsItsValueLimits(): void
    {
        $group = CheckboxGroup::new('toppings')
            ->addOption(new GroupOption('cheese', 'Cheese'))
            ->addOption(new GroupOption('olives', 'Olives'));

        $this->assertArrayNotHasKey('min_values', $group->jsonSerialize());

        $group->setMinValues(1)->setMaxValues(2);

        $this->assertTrue(isset($group->max_values));
        $this->assertSame(1, $group->jsonSerialize()['min_values']);
        $this->assertSame(2, $group->jsonSerialize()['max_values']);
    }
}
