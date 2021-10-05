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
        $choices = new Collection([], 'name');

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
        $options = new Collection([], 'name');

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->push($this->factory->create(Option::class, $option, true));
        }

        return $options;
    }
}
