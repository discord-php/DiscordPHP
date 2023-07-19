<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Request;

use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Command\Option as CommandOption;
use Discord\Parts\Part;

/**
 * Represents an option received with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-interaction-data-option-structure
 *
 * @since 7.0.0
 *
 * @property string                     $name    Name of the parameter.
 * @property int                        $type    Type of the option.
 * @property string|int|float|bool|null $value   Value of the option resulting from user input.
 * @property Collection|Option[]|null   $options Present if this option is a group or subcommand.
 * @property bool|null                  $focused `true` if this option is the currently focused option for autocomplete.
 */
class Option extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'name',
        'type',
        'value',
        'options',
        'focused',
    ];

    /**
     * Gets the options of the interaction.
     *
     * @return Collection|Option[]|null $options
     */
    protected function getOptionsAttribute(): ?Collection
    {
        if (! isset($this->attributes['options']) && ! in_array($this->type, [CommandOption::SUB_COMMAND, CommandOption::SUB_COMMAND_GROUP])) {
            return null;
        }

        $options = Collection::for(Option::class, 'name');

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->pushItem($this->createOf(Option::class, $option));
        }

        return $options;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
