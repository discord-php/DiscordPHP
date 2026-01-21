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

/**
 * A Checkbox Group is an interactive component for selecting one or many options via checkboxes. Checkbox Groups are available in modals and must be placed inside a Label.
 *
 * @link https://discord.com/developers/docs/components/reference#checkbox-group
 *
 * @since 10.46.0
 *
 * @property int       $type      21 for a radio group.
 * @property ?int|null $id        Optional identifier for component.
 * @property string    $custom_id Custom ID to send with interactive component.
 * @property array     $options   List of options to render.
 * @property ?bool     $required  Whether selecting an option is required or not.
 */
abstract class Group extends Interactive
{
    /**
     * List of options to render.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Whether selecting an option is required or not.
     *
     * @var bool|null
     */
    protected $required;

    /**
     * List of options to render.
     *
     * @param GroupOption[] $options List of GroupOption objects.
     *
     * @return $this
     */
    public function setOptions(array $options = []): self
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }

        return $this;
    }

    /**
     * Adds an option to the group. Maximum 10 options.
     *
     * @param GroupOption $option Option to add.
     *
     * @throws \OverflowException
     * @throws \UnexpectedValueException
     *
     * @return $this
     */
    public function addOption($option): self
    {
        if (count($this->options) >= 10) {
            throw new \OverflowException('You can only have 10 options per radio group.');
        }

        $value = $option->getValue();

        foreach ($this->options as $other) {
            if ($other->getValue() === $value) {
                throw new \UnexpectedValueException('Another value already has the same value. These must not be the same.');
            }
        }

        $this->options[] = $option;

        return $this;
    }

    /**
     * Removes an option from the group.
     *
     * @param GroupOption $option Option to remove.
     *
     * @return $this
     */
    public function removeOption($option): self
    {
        if (($idx = array_search($option, $this->options)) !== false) {
            array_splice($this->options, $idx, 1);
        }

        return $this;
    }

    /**
     * Gets the options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set if this component is required to be filled, default false. (Modal only).
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
     * Gets whether this component is required to be filled.
     *
     * @return bool|null
     */
    public function getRequired(): ?bool
    {
        return $this->required ?? null;
    }
}
