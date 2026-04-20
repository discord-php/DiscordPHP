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

it('new() creates a builder with title, custom_id, and components', function () {
    $label = new Label();
    $builder = ModalBuilder::new('title', 'custom_id', [$label]);

    expect($builder->getTitle())->toBe('title');
    expect($builder->getCustomId())->toBe('custom_id');
    expect($builder->getComponents())->toBe([$label]);
});

it('addComponent appends a component', function () {
    $label = new Label();
    $builder = new ModalBuilder();
    $builder->addComponent($label);

    expect($builder->getComponents())->toBe([$label]);
});

it('setComponents replaces all components', function () {
    $label = new Label();
    $builder = new ModalBuilder();
    $builder->setComponents([$label]);

    expect($builder->getComponents())->toBe([$label]);
});

it('setTitle throws when title exceeds 45 characters', function () {
    $builder = new ModalBuilder();
    $builder->setTitle(str_repeat('a', 101));
})->throws(\LogicException::class, 'Modal title can not be longer than 45 characters');

it('setCustomId throws when custom ID exceeds 100 characters', function () {
    $builder = new ModalBuilder();
    $builder->setCustomId(str_repeat('a', 101));
})->throws(\LogicException::class, 'Custom ID must be maximum 100 characters.');

it('addComponent throws when component limit is reached', function () {
    $builder = new ModalBuilder();
    $builder->setComponents([new Label(), new Label(), new Label(), new Label(), new Label()]);
    $builder->addComponent(new Label());
})->throws(\OverflowException::class, 'You can only have 5 components per modal.');

