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

namespace Discord\Builders\Components;

use Discord\Builders\Builder;
use Discord\Parts\Channel\Message\OverflowMenuGroup as OverflowMenuGroupPart;
use Discord\Parts\Part;
use JsonSerializable;

/** An optionally labeled group of menu action items, not itself a component. */
class OverflowMenuGroup extends Builder implements JsonSerializable
{
    /** @var list<MenuActionItem> */
    private array $components = [];
    private ?string $label = null;

    public static function new(?string $label = null): self
    {
        return (new self())->setLabel($label);
    }

    public static function fromPart(Part $part): self
    {
        if (! $part instanceof OverflowMenuGroupPart) {
            throw new \InvalidArgumentException('Expected an overflow menu group part.');
        }

        $group = self::new($part->label);
        foreach ($part->components as $item) {
            $group->addComponent(MenuActionItem::fromPart($item));
        }

        return $group;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function addComponent(MenuActionItem $component): self
    {
        $this->components[] = $component;

        return $this;
    }

    /** @return list<MenuActionItem> */
    public function getComponents(): array
    {
        return $this->components;
    }

    public function jsonSerialize(): array
    {
        if ($this->components === []) {
            throw new \DomainException('An overflow menu group requires at least one action.');
        }

        $data = ['components' => $this->components];

        if ($this->label !== null) {
            $data['label'] = $this->label;
        }

        return $data;
    }
}
