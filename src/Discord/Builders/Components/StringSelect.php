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

namespace Discord\Builders\Components;

/**
 * Select menu for picking from defined text options.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#select-menus
 *
 * @since 10.0.0 Renamed from SelectMenu to StringSelect
 * @since 7.0.0
 *
 * @property int          $type        3 for string select.
 * @property Option[]     $options     Specified choices in a select menu; max 25.
 * @property ?string|null $placeholder Placeholder text if nothing is selected or default; max 150 characters.
 * @property ?int|null    $min_values  Minimum number of items that must be chosen (defaults to 1); min 0, max 25.
 * @property ?int|null    $max_values  Maximum number of items that can be chosen (defaults to 1); max 25.
 * @property ?bool|null   $required    Whether the string select is required to answer in a modal (defaults to true).
 * @property ?bool|null   $disabled    Whether select menu is disabled in a message (defaults to false).
 */
class StringSelect extends SelectMenu
{
    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_STRING_SELECT;

    /**
     * Array of options that the select menu has.
     *
     * @var Option[]
     */
    protected $options = [];

    /**
     * Sets whether the select menu is required. (Modal only).
     *
     * @param bool|null $required
     *
     * @return $this
     */
    public function setRequired(?bool $required = null): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Adds an option to the select menu. Maximum 25 options.
     *
     * @param Option $option Option to add.
     *
     * @throws \OverflowException
     * @throws \UnexpectedValueException
     *
     * @return $this
     */
    public function addOption(Option $option): self
    {
        if (count($this->options) > 25) {
            throw new \OverflowException('You can only have 25 options per select menu.');
        }

        $value = $option->getValue();

        // didn't wanna use a hashtable here so that we can keep the order of options
        foreach ($this->options as $other) {
            if ($other->getValue() == $value) {
                throw new \UnexpectedValueException('Another value already has the same value. These must not be the same.');
            }
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Removes an option from the select menu.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option): self
    {
        if (($idx = array_search($option, $this->options)) !== null) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Returns the array of options that the select menu has.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
