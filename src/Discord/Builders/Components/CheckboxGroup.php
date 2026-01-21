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
 * @property int                   $type       22 for checkbox group.
 * @property ?int|null             $id         Optional identifier for component.
 * @property string                $custom_id  Developer-defined identifier for the input; 1-100 characters.
 * @property CheckboxGroupOption[] $options    List of options to render; min 1, max 10.
 * @property ?int|null             $min_values Minimum number of items that must be chosen; min 0, max 10 (defaults to 1).
 * @property ?int|null             $max_values Maximum number of items that can be chosen; min 1, max 10 (defaults to the number of options).
 * @property ?bool|null            $required   Whether selecting within the group is required (defaults to `true`).
 */
class CheckboxGroup extends Group
{
    /**
     * @inheritDoc
     */
    public const USAGE = ['Modal'];

    /**
     * @inheritDoc
     */
    protected $type = ComponentObject::TYPE_CHECKBOX_GROUP;

    /**
     * Creates a new checkbox group.
     *
     * @param string|null $custom_id custom ID of the checkbox group. If not given, a UUID will be used.
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(?string $custom_id = null)
    {
        $this->setCustomId($custom_id ?? self::generateUuid());
    }
    
    /**
     * Creates a new checkbox group component.
     *
     * @param string|null $custom_id ID for the checkbox group.
     *
     * @return self
     */
    public static function new(?string $custom_id = null): self
    {
        return new self($custom_id);
    }

    /**
     * Sets the minimum number of items that must be chosen.
     *
     * @param int|null $min_values Default `1`, minimum `0` and maximum `10`. `null` to set as default.
     *
     * @throws \OutOfRangeException
     *
     * @return $this
     */
    public function setMinValues(?int $min_values = null): self
    {
        if (isset($min_values) && ($min_values < 1 || $min_values > 10)) {
            throw new \OutOfRangeException('Number must be between 0 and 10 inclusive.');
        }

        $this->min_values = $min_values;

        return $this;
    }

    /**
     * Gets the minimum number of items that must be chosen.
     *
     * @return int|null
     */
    public function getMinValues(): ?int
    {
        return $this->min_values ?? null;
    }

    /**
     * Sets the maximum number of items that can be chosen.
     *
     * @param int|null $max_values Default `1` and maximum `10`. `null` to set as default.
     *
     * @throws \OutOfRangeException
     *
     * @return $this
     */
    public function setMaxValues(?int $max_values = null): self
    {
        if (isset($max_values) && ($max_values < 1 || $max_values > 10)) {
            throw new \OutOfRangeException('Number must be less than or equal to 10.');
        }

        $this->max_values = $max_values;

        return $this;
    }

    /**
     * Gets the maximum number of items that can be chosen.
     *
     * @return int|null
     */
    public function getMaxValues(): ?int
    {
        return $this->max_values ?? null;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
            'options' => $this->options,
        ];

        if (count($this->options) < 1 || count($this->options) > 10) {
            throw new \DomainException('CheckboxGroup must have between 1 and 10 options.');
        }
        
        if (isset($this->min_values)) {
            $content['min_values'] = $this->min_values;
        }

        if (isset($this->max_values)) {
            $content['max_values'] = $this->max_values;
        }

        if (isset($this->required)) {
            $content['required'] = $this->required;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
