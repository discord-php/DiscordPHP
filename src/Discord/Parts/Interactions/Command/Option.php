<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Helpers\Collection;
use Discord\Parts\Part;

use function Discord\poly_strlen;

/**
 * Option represents an array of options that can be given to a command.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-option-structure
 *
 * @property int                      $type          Type of the option.
 * @property string                   $name          Name of the option. CHAT_INPUT option name is lowercase.
 * @property string                   $description   1-100 character description.
 * @property bool                     $required      If the parameter is required or optional--default false.
 * @property Collection|Choice[]|null $choices       Choices for STRING, INTEGER, and NUMBER types for the user to pick from, max 25. Only for slash commands.
 * @property Collection|Option[]      $options       Sub-options if applicable.
 * @property array                    $channel_types If the option is a channel type, the channels shown will be restricted to these types.
 * @property int|float                $min_value     If the option is an INTEGER or NUMBER type, the minimum value permitted.
 * @property int|float                $max_value     If the option is an INTEGER or NUMBER type, the maximum value permitted.
 * @property bool                     $autocomplete  Enable autocomplete interactions for this option.
 */
class Option extends Part
{
    public const SUB_COMMAND = 1;
    public const SUB_COMMAND_GROUP = 2;
    public const STRING = 3;
    public const INTEGER = 4; // Any integer between -2^53 and 2^53
    public const BOOLEAN = 5;
    public const USER = 6;
    public const CHANNEL = 7; // Includes all channel types + categories
    public const ROLE = 8;
    public const MENTIONABLE = 9; // Includes users and roles
    public const NUMBER = 10; // Any double between -2^53 and 2^53
    public const ATTACHMENT = 11;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'type',
        'name',
        'description',
        'required',
        'choices',
        'options',
        'channel_types',
        'min_value',
        'max_value',
        'autocomplete',
    ];

    /**
     * Gets the choices attribute.
     *
     * @return Collection|Choice[]|null A collection of choices.
     */
    protected function getChoicesAttribute(): ?Collection
    {
        if (! isset($this->attributes['choices'])) {
            return null;
        }

        $choices = Collection::for(Choice::class, null);

        foreach ($this->attributes['choices'] as $choice) {
            $choices->push($this->factory->create(Choice::class, $choice, true));
        }

        return $choices;
    }

    /**
     * Gets the options attribute.
     *
     * @return Collection|Option[] A collection of options.
     */
    protected function getOptionsAttribute(): Collection
    {
        $options = Collection::for(Option::class, null);

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->push($this->factory->create(Option::class, $option, true));
        }

        return $options;
    }

    /**
     * Sets the type of the option.
     *
     * @param int $type type of the option
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setType(int $type): self
    {
        if ($type < 1 || $type > 11) {
            throw new \InvalidArgumentException('Invalid type provided.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Sets the name of the option.
     * CHAT_INPUT command option names must match the following regex ^[\w-]{1,32}$ with the unicode flag set.
     * If there is a lowercase variant of any letters used, you must use those.
     * Characters with no lowercase variants and/or uncased letters are still allowed.
     *
     * @param string $name name of the option. Slash command option names are lowercase.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if ($name && poly_strlen($name) > 32) {
            throw new \LengthException('Name must be less than or equal to 32 characters.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Sets the description of the option.
     *
     * @param string $description description of the option
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        if ($description && poly_strlen($description) > 100) {
            throw new \LengthException('Description must be less than or equal to 100 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the requirement of the option.
     *
     * @param bool $required requirement of the option
     *
     * @return $this
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Sets the channel types of the option.
     *
     * @param array $types types of the channel
     *
     * @return $this
     */
    public function setChannelTypes(array $types): self
    {
        $this->channel_types = $types;

        return $this;
    }

    /**
     * Adds an option to the option.
     *
     * @param Option $option The option
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addOption(Option $option): self
    {
        if (count($this->options) >= 25) {
            throw new \OverflowException('Option can not have more than 25 parameters.');
        }

        $this->attributes['options'][] = $option->getRawAttributes();

        return $this;
    }

    /**
     * Adds a choice to the option (Only for slash commands).
     *
     * @param Choice $choice The choice
     *
     * @throws \OverflowException
     *
     * @return $this
     */
    public function addChoice(Choice $choice): self
    {
        if (count($this->choices ?? []) >= 25) {
            throw new \OverflowException('Option can only have a maximum of 25 Choices.');
        }

        $this->attributes['choices'][] = $choice->getRawAttributes();

        return $this;
    }

    /**
     * Removes an option.
     *
     * @param string|Option $option Option object or name to remove.
     *
     * @return $this
     */
    public function removeOption($option): self
    {
        if ($option instanceof Option) {
            $option = $option->name;
        }

        if (! empty($this->attributes['options'])) {
            foreach ($this->attributes['options'] as $idx => $opt) {
                if ($opt['name'] == $option) {
                    unset($this->attributes['options'][$idx]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Removes a choice (Only for slash commands).
     *
     * @param string|Choice $choice Choice object or name to remove.
     *
     * @return $this
     */
    public function removeChoice($choice): self
    {
        if ($choice instanceof Choice) {
            $choice = $choice->name;
        }

        if (! empty($this->attributes['choices'])) {
            foreach ($this->attributes['choices'] as $idx => $cho) {
                if ($cho['name'] == $choice) {
                    unset($this->attributes['choices'][$idx]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Sets the minimum value permitted.
     *
     * @param int|float $min_value integer for INTEGER options, double for NUMBER options
     *
     * @return $this
     */
    public function setMinValue($min_value): self
    {
        $this->min_value = $min_value;

        return $this;
    }

    /**
     * Sets the minimum value permitted.
     *
     * @param int|float $min_value integer for INTEGER options, double for NUMBER options
     *
     * @return $this
     */
    public function setMaxValue($max_value): self
    {
        $this->max_value = $max_value;

        return $this;
    }

    /**
     * Sets the autocomplete interactions for this option.
     *
     * @param bool $autocomplete enable autocomplete interactions for this option
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setAutoComplete(bool $autocomplete): self
    {
        if ($autocomplete) {
            if (! empty($this->attributes['choices'])) {
                throw new \InvalidArgumentException('Autocomplete may not be set to true if choices are present.');
            }

            if (! in_array($this->type, [self::STRING, self::INTEGER, self::NUMBER])) {
                throw new \InvalidArgumentException('Autocomplete may be only set to true if option type is STRING, INTEGER, or NUMBER.');
            }
        }

        $this->autocomplete = $autocomplete;

        return $this;
    }
}
