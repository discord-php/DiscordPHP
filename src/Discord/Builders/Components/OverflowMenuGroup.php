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

/**
 * Builds an optionally labeled group of overflow menu actions.
 *
 * A group is a wrapper, not a component. Each action item counts toward the message component limit.
 *
 * @property string|null                $label      Optional group heading.
 * @property-read list<MenuActionItem>  $components Actions in display order.
 *
 * @see OverflowMenuGroupPart For the received group.
 */
class OverflowMenuGroup extends Builder implements JsonSerializable
{
    /** @var list<MenuActionItem> */
    protected array $components = [];
    protected ?string $label = null;

    /**
     * Creates an empty group. Add at least one action before serializing.
     *
     * @param string|null $label Optional group heading.
     * @return self
     */
    public static function new(?string $label = null): self
    {
        return (new self())->setLabel($label);
    }

    /**
     * Rebuilds an editable group from a received message part.
     *
     * @param Part $part A received OverflowMenuGroupPart instance.
     * @throws \InvalidArgumentException If the part is not a OverflowMenuGroupPart.
     * @return self
     */
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

    /**
     * Sets or clears the group heading.
     *
     * @param string|null $label Heading, or null to omit.
     * @return self
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Returns the group heading, or null for an unlabeled group.
     *
     * @return string|null
     */
    protected function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Appends an action item in display order.
     *
     * @param MenuActionItem $component Action to append.
     * @return self
     */
    public function addComponent(MenuActionItem $component): self
    {
        $this->components[] = $component;

        return $this;
    }

    /**
     * Returns the actions in display order.
     *
     * @return list<MenuActionItem>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Builds the untyped group payload.
     *
     * @throws \DomainException If the group contains no actions.
     * @return array{components: list<MenuActionItem>, label?: string}
     */
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
