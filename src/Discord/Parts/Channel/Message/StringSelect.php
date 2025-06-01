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
 * A String Select is an interactive component that allows users to select one or more provided options in a message.
 *
 * String Selects can be configured for both single-select and multi-select behavior. When a user finishes making their choice(s) your app receives an interaction.
 *
 * String Selects must be placed inside an Action Row and are only available in messages. An Action Row can contain only one select menu and cannot contain buttons if it has a select menu.
 *
 * @link https://discord.com/developers/docs/components/reference#string-select
 *
 * @since 10.11.0
 *
 * @property int                                        $type           3 for string select.
 * @property string|null                                $id             Optional identifier for component.
 * @property string                                     $custom_id      ID for the select menu; max 100 characters
 * @property ExCollectionInterface|StringSelectOption[] $options        Specified choices in a select menu; max 25.
 * @property string|null                                $placeholder    Placeholder text if nothing is selected or default; max 150 characters
 * @property int|null                                   $min_values     Minimum number of items that must be chosen (defaults to 1); min 0, max 25.
 * @property int|null                                   $max_values     Maximum number of items that can be chosen (defaults to 1); max 25.
 * @property bool|null                                  $disabled       Whether select menu is disabled (defaults to false).
 */
class StringSelect extends SelectMenu
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'custom_id',
        'options',
        'placeholder',
        'min_values',
        'max_values',
        'disabled',
    ];

    /**
     * Gets the partial options attribute.
     *
     * @return ExCollectionInterface|StringSelectOption[]
     */
    protected function getOptionsAttribute(): ExCollectionInterface
    {
        $collection = Collection::for(StringSelectOption::class, null);

        foreach ($this->attributes['options'] ?? [] as $item) {
            $collection->pushItem($this->createOf(StringSelectOption::class, $item));
        }

        return $collection;
    }
}
