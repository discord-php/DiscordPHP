<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Discord\Enums\ApplicationCommandOptionType;
use Discord\Helpers\Collection;
use Discord\Parts\Part;

use function Discord\poly_strlen;

/**
 * Option represents an array of options that can be given to a command.
 *
 * @author David Cole <david.cole1340@gmail.com>
 *
 * @property ApplicationCommandOptionType $type          Type of the option.
 * @property string                       $name          Name of the option.
 * @property string                       $description   1-100 character description.
 * @property bool                         $required      if the parameter is required or optional--default false.
 * @property Collection|Choice[]          $choices       choices for STRING, INTEGER, and NUMBER types for the user to pick from, max 25.
 * @property Collection|Option[]          $options       Sub-options if applicable.
 * @property array                        $channel_types If the option is a channel type, the channels shown will be restricted to these types.
 */
class Option extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['type', 'name', 'description', 'required', 'choices', 'options', 'channel_types'];

    /**
     * @inheritdoc
     */
    protected $visible = ['choices', 'options'];

    /**
     * Gets the choices attribute.
     *
     * @return Collection|Choices[] A collection of choices.
     */
    protected function getChoicesAttribute(): Collection
    {
        $choices = new Collection([], null);

        foreach ($this->attributes['choices'] ?? [] as $choice) {
            $choices->push($this->factory->create(Choice::class, $choice, true));
        }

        return $choices;
    }

    /**
     * Gets the options attribute.
     *
     * @return Collection|Options[] A collection of options.
     */
    protected function getOptionsAttribute(): Collection
    {
        $options = new Collection([], null);

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->push($this->factory->create(Option::class, $option, true));
        }

        return $options;
    }

    /**
     * Sets the type of the option.
     *
     * @param ApplicationCommandOptionType $type type of the option
     *
     * @return $this
     */
    public function setType(ApplicationCommandOptionType $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Sets the name of the option.
     *
     * @param string $name name of the option
     *
     * @return $this
     */
    public function setName(string $name)
    {
        if ($name && poly_strlen($name) > 32) {
            throw new \InvalidArgumentException('Name must be less than or equal to 32 characters.');
        }

        $this->name = $name;
        return $this;
    }

    /**
     * Sets the description of the option.
     *
     * @param string $description description of the option
     *
     * @return $this
     */
    public function setDescription(string $description)
    {
        if ($description && poly_strlen($description) > 100) {
            throw new \InvalidArgumentException('Description must be less than or equal to 100 characters.');
        }

        $this->description = $description;
        return $this;
    }

    /**
     * Sets the requirement of the option.
     *
     * @param bool $require requirement of the option
     *
     * @return $this
     */
    public function setRequire(bool $require)
    {
        $this->required = $require;
        return $this;
    }

    /**
     * Sets the channel types of the option.
     *
     * @param array $types types of the channel
     *
     * @return $this
     */
    public function setChannelTypes(array $types)
    {
        $this->channel_types = $types;
        return $this;
    }

    /**
     * Adds an option to the option.
     *
     * @param Option $option The option
     *
     * @return $this
     */
    public function addOption(Option $option)
    {
        if (count($this->options) >= 25) {
            throw new \RangeException('Option can not have more than 25 parameters.');
        }

        $this->attributes['options'][] = $option;
        return $this;
    }

    /**
     * Adds a choice to the option.
     *
     * @param Choice $choice The choice
     *
     * @return $this
     */
    public function addChoice(Choice $choice)
    {
        if (count($this->choices) >= 25) {
            throw new \RangeException('Option can only have a maximum of 25 Choices.');
        }

        $this->attributes['choices'][] = $choice;
        return $this;
    }

    /**
     * Removes an option.
     *
     * @param Option $option Option to remove.
     *
     * @return $this
     */
    public function removeOption(Option $option)
    {
        if ($opt = $this->attributes['options']->offsetGet($option->name)) {
            $this->attributes['options']->offsetUnset($opt);
        }

        return $this;
    }

    /**
     * Removes a choice.
     *
     * @param Choice $choice Choice to remove
     *
     * @return $this
     */
    public function removeChoice(Choice $choice)
    {
        if ($cho = $this->attributes['choices']->offsetGet($choice->name)) {
            $this->attributes['choices']->offsetUnset($cho);
        }

        return $this;
    }
}
