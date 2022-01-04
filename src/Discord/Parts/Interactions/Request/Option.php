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

use Discord\Parts\Part;

/**
 * Represents an option received with an interaction.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-object-application-command-interaction-data-option-structure
 *
 * @property string                   $name    Name of the option.
 * @property int                      $type    Type of the option.
 * @property string                   $value   Value of the option.
 * @property Collection|Option[]|null $options Sub-options if applicable.
 * @property bool                     $focused Whether this option is the currently focused option for autocomplete.
 */
class Option extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['name', 'type', 'value', 'options', 'focused'];

    /**
     * Sets the sub-options of the option.
     *
     * @param array $options
     */
    protected function setOptionsAttribute($options)
    {
        foreach ($options as $option) {
            $this->options->push($this->factory->create(Option::class, $option, true));
        }
    }
}
