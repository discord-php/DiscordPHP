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

/** An overflow menu for an action row or a section accessory. */
class OverflowMenu extends ComponentObject
{
    public const USAGE = ['Message'];

    protected $type = self::TYPE_OVERFLOW_MENU;

    /** @var list<OverflowMenuGroup> */
    private array $groups = [];

    public static function new(): self
    {
        return new self();
    }

    /** Set Discord's optional numeric component ID. This is not a custom ID. */
    public function setId(int|string|null $id = null): self
    {
        if (is_string($id) && filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('A component ID must be an integer.');
        }

        $this->id = $id === null ? null : (int) $id;

        return $this;
    }

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

    public function addGroup(OverflowMenuGroup $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    /** @return list<OverflowMenuGroup> */
    public function getGroups(): array
    {
        return $this->groups;
    }

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
