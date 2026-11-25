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

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * A received group of actions in an overflow menu.
 *
 * @property string|null                $label      Optional group heading.
 * @property-read ExCollectionInterface<MenuActionItem>  $components Actions in display order.
 */
class OverflowMenuGroup extends Part
{
    protected $fillable = ['label', 'components'];

    /**
     * Hydrates the received actions in display order.
     *
     * @return ExCollectionInterface<MenuActionItem>
     */
    protected function getComponentsAttribute(): ExCollectionInterface
    {
        // IDs are optional. Positional keys keep all items in display order
        // without conflating a supplied ID with a generated array offset.
        return $this->attributeCollectionHelper('components', MenuActionItem::class, null);
    }
}
