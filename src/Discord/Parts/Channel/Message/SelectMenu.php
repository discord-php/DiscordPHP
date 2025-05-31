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

namespace Discord\Parts\Channel\Message;

use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;

/**
 * Select menus are interactive components that allow users to select one or
 * more options from a dropdown list in messages.
 * On desktop, clicking on a select menu opens a dropdown-style UI.
 * On mobile, tapping a select menu opens up a half-sheet with the options.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#select-menus
 *
 * @since 10.11.0
 */
abstract class SelectMenu extends Interactive
{
    protected function getDefaultValuesAttribute(): ?ExCollectionInterface
    {
        if (! isset($this->attributes['default_values'])) {
            return null;
        }

        $collection = Collection::for(DefaultValue::class);

        foreach ($this->attributes['default_values'] as $item) {
            $collection->pushItem($this->createOf(DefaultValue::class, $item));
        }

        return $collection;
    }
}
