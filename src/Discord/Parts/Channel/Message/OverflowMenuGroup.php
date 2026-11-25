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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * A group of actions in an overflow menu.
 *
 * @property string|null          $label
 * @property-read MenuActionItem[] $components
 */
class OverflowMenuGroup extends Part
{
    protected $fillable = ['label', 'components'];

    /** @return list<MenuActionItem> */
    protected function getComponentsAttribute(): array
    {
        $items = $this->attributes['components'] ?? [];

        foreach ($items as &$item) {
            if (! $item instanceof MenuActionItem) {
                $item = $this->createOf(MenuActionItem::class, $item);
            }
        }
        $this->attributes['components'] = $items;

        return array_values($items);
    }
}
