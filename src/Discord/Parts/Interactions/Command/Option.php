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
 * @link https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-option-structure
 *
 * @since 7.0.0
 *
 * @property int                      $type                      Type of the option.
 * @property string                   $name                      Name of the option.
 * @property ?string[]|null           $name_localizations        Localization dictionary for the name field. Values follow the same restrictions as name.
 * @property string                   $description               1-100 character description.
 * @property ?string[]|null           $description_localizations Localization dictionary for the description field. Values follow the same restrictions as description.
 * @property bool|null                $required                  If the parameter is required or optional--default false.
 * @property Collection|Choice[]|null $choices                   Choices for STRING, INTEGER, and NUMBER types for the user to pick from, max 25. Only for slash commands.
 * @property Collection|Option[]      $options                   Sub-options if applicable.
 * @property array|null               $channel_types             If the option is a channel type, the channels shown will be restricted to these types.
 * @property int|float|null           $min_value                 If the option is an INTEGER or NUMBER type, the minimum value permitted.
 * @property int|float|null           $max_value                 If the option is an INTEGER or NUMBER type, the maximum value permitted.
 * @property int|null                 $min_length                For option type `STRING`, the minimum allowed length (minimum of `0`, maximum of `6000`).
 * @property int|null                 $max_length                For option type `STRING`, the maximum allowed length (minimum of `1`, maximum of `6000`).
 * @property bool|null                $autocomplete              Enable autocomplete interactions for this option.
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
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'name',
        'name_localizations',
        'description',
        'description_localizations',
        'required',
        'choices',
        'options',
        'channel_types',
        'min_value',
        'max_value',
        'min_length',
        'max_length',
        'autocomplete',
    ];

    /**
     * Gets the choices attribute.
     *
     * @return Collection|Choice[]|null A collection of choices.
     */
    protected function getChoicesAttribute(): ?Collection
    {
        if (! isset($this->attributes['choices']) && ! in_array($this->type, [self::STRING, self::INTEGER, self::NUMBER])) {
            return null;
        }

        $choices = Collection::for(Choice::class, null);

        foreach ($this->attributes['choices'] ?? [] as $choice) {
            $choices->pushItem($this->createOf(Choice::class, $choice));
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
            $options->pushItem($this->createOf(Option::class, $option));
        }

        return $options;
    }

    /**
     * Sets the type of the option.
     *
     * @param int $type type of the option.
     *
     * @throws \InvalidArgumentException `$type` is not 1-11.
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
     * @throws \LengthException `$name` is more than 32 characters.
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        if (poly_strlen($name) > 32) {
            throw new \LengthException('Name must be less than or equal to 32 characters.');
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Sets the name of the option in another language.
     * CHAT_INPUT command option names must match the following regex ^[\w-]{1,32}$ with the unicode flag set.
     * If there is a lowercase variant of any letters used, you must use those.
     * Characters with no lowercase variants and/or uncased letters are still allowed.
     *
     * @param string      $locale Discord locale code.
     * @param string|null $name   Localized name of the option. Slash command option names are lowercase.
     *
     * @throws \LengthException `$name` is more than 32 characters.
     *
     * @return $this
     */
    public function setNameLocalization(string $locale, ?string $name): self
    {
        if (isset($name) && poly_strlen($name) > 32) {
            throw new \LengthException('Name must be less than or equal to 32 characters.');
        }

        $this->attributes['name_localizations'][$locale] = $name;

        return $this;
    }

    /**
     * Sets the description of the option.
     *
     * @param string $description description of the option.
     *
     * @throws \LengthException `$description` is more than 100 characters.
     *
     * @return $this
     */
    public function setDescription(string $description): self
    {
        if (poly_strlen($description) > 100) {
            throw new \LengthException('Description must be less than or equal to 100 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the description of the option in another language.
     *
     * @param string      $locale      Discord locale code.
     * @param string|null $description Localized description of the option.
     *
     * @throws \LengthException `$description` is more than 100 characters.
     *
     * @return $this
     */
    public function setDescriptionLocalization(string $locale, ?string $description): self
    {
        if (isset($description) && poly_strlen($description) > 100) {
            throw new \LengthException('Description must be less than or equal to 100 characters.');
        }

        $this->attributes['description_localizations'][$locale] = $description;

        return $this;
    }

    /**
     * Sets the requirement of the option.
     *
     * @param bool $required requirement of the option (default false)
     *
     * @return $this
     */
    public function setRequired(bool $required = false): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Sets the channel types of the option.
     *
     * @param array|null $types types of the channel.
     *
     * @return $this
     */
    public function setChannelTypes(?array $types): self
    {
        $this->channel_types = $types;

        return $this;
    }

    /**
     * Adds an option to the option.
     *
     * @param Option $option The option.
     *
     * @throws \OverflowException Command exceeds maximum 25 sub options.
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
     * @param Choice $choice The choice.
     *
     * @throws \OverflowException Command exceeds maximum 25 choices.
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

        foreach ($this->attributes['options'] ?? [] as $idx => $opt) {
            if ($opt['name'] == $option) {
                unset($this->attributes['options'][$idx]);
                break;
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

        foreach ($this->attributes['choices'] ?? [] as $idx => $cho) {
            if ($cho['name'] == $choice) {
                unset($this->attributes['choices'][$idx]);
                break;
            }
        }

        return $this;
    }

    /**
     * Sets the minimum value permitted.
     *
     * @param int|float|null $min_value integer for INTEGER options, double for NUMBER options.
     *
     * @return $this
     */
    public function setMinValue($min_value): self
    {
        $this->min_value = $min_value;

        return $this;
    }

    /**
     * Sets the maximum value permitted.
     *
     * @param int|float|null $max_value integer for INTEGER options, double for NUMBER options
     *
     * @return $this
     */
    public function setMaxValue($max_value): self
    {
        $this->max_value = $max_value;

        return $this;
    }

    /**
     * Sets the minimum length permitted.
     *
     * @param int|null $min_length For option type `STRING`, the minimum allowed length (minimum of `0`).
     *
     * @throws \LogicException
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMinLength(?int $min_length): self
    {
        if (isset($min_length)) {
            if ($this->type != self::STRING) {
                throw new \LogicException('Minimum length can be only set on Option type STRING.');
            } elseif ($min_length < 0 || $min_length > 6000) {
                throw new \LengthException('Minimum length must be between 0 and 6000 inclusive.');
            }
        }

        $this->min_length = $min_length;

        return $this;
    }

    /**
     * Sets the maximum length permitted.
     *
     * @param int|null $max_length For option type `STRING`, the maximum allowed length (minimum of `1`).
     *
     * @throws \LogicException
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMaxLength(?int $max_length): self
    {
        if (isset($max_length)) {
            if ($this->type != self::STRING) {
                throw new \LogicException('Maximum length can be only set on Option type STRING.');
            } elseif ($max_length < 1 || $max_length > 6000) {
                throw new \LengthException('Maximum length must be between 1 and 6000 inclusive.');
            }
        }

        $this->max_length = $max_length;

        return $this;
    }

    /**
     * Sets the autocomplete interactions for this option.
     *
     * @param bool|null $autocomplete enable autocomplete interactions for this option.
     *
     * @throws \DomainException Command option type is not string/integer/number.
     *
     * @return $this
     */
    public function setAutoComplete(?bool $autocomplete): self
    {
        if ($autocomplete) {
            if (! empty($this->attributes['choices'])) {
                throw new \DomainException('Autocomplete may not be set to true if choices are present.');
            }

            if (! in_array($this->type, [self::STRING, self::INTEGER, self::NUMBER])) {
                throw new \DomainException('Autocomplete may be only set to true if option type is STRING, INTEGER, or NUMBER.');
            }
        }

        $this->autocomplete = $autocomplete;

        return $this;
    }
}
