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

use Discord\Parts\Channel\Message\OverflowMenu as OverflowMenuPart;
use Discord\Parts\Part;

/**
 * Builds an overflow menu for an action row or a Section accessory.
 *
 * The menu and its action items each count toward the message component limit.
 * Group wrappers are not components. This builder does not register click handlers.
 *
 * @property-read int                      $type   Component type 25.
 * @property-write int|string|null                      $id     Optional numeric component ID, not a custom ID.
 * @property-read list<OverflowMenuGroup>  $groups Groups in display order.
 *
 * @see OverflowMenuPart For the received message component.
 */
class OverflowMenu extends ComponentObject
{
    public const USAGE = ['Message'];

    protected $type = ComponentObject::TYPE_OVERFLOW_MENU;

    /** @var list<OverflowMenuGroup> */
    protected array $groups = [];

    /**
     * Creates an empty menu. Add at least one group before serializing.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Sets Discord's optional numeric component ID, not an interaction custom ID.
     *
     * @param int|string|null $id An integer, an integer string, or null to clear.
     * @throws \InvalidArgumentException If a string does not represent an integer.
     * @return self
     */
    protected function setId(int|string|null $id = null): self
    {
        if (is_string($id) && filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('A component ID must be an integer.');
        }

        $this->id = $id === null ? null : (int) $id;

        return $this;
    }

    /**
     * Rebuilds an editable menu from a received message part.
     *
     * @param Part $part A received OverflowMenuPart instance.
     * @throws \InvalidArgumentException If the part is not a OverflowMenuPart.
     * @return self
     */
    public static function fromPart(Part $part): self
    {
        if (! $part instanceof OverflowMenuPart) {
            throw new \InvalidArgumentException('Expected an overflow menu part.');
        }

        $menu = self::new();
        $menu->id = $part->id;
        foreach ($part->groups as $group) {
            $menu->addGroup(OverflowMenuGroup::fromPart($group));
        }

        return $menu;
    }

    /**
     * Appends a group to the menu in display order.
     *
     * @param OverflowMenuGroup $group Group to append.
     * @return self
     */
    public function addGroup(OverflowMenuGroup $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * Returns the menu groups in display order.
     *
     * @return list<OverflowMenuGroup>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Builds the API payload for this menu.
     *
     * @throws \DomainException If the menu contains no groups.
     * @return array{type: int, groups: list<OverflowMenuGroup>, id?: int}
     */
    public function jsonSerialize(): array
    {
        if ($this->groups === []) {
            throw new \DomainException('An overflow menu requires at least one group.');
        }

        $data = [
            'type' => $this->type,
            'groups' => $this->groups,
        ];

        if (isset($this->id)) {
            $data['id'] = (int) $this->id;
        }

        return $data;
    }
}
