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
use Discord\Repository\Interaction\OptionRepository;

/**
 * Represents the data associated with an interaction.
 *
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-data-structure
 *
 * @property string           $id             ID of the invoked command.
 * @property string           $name           Name of the invoked command.
 * @property int              $type           The type of the invoked command.
 * @property Resolved|null    $resolved       Resolved users, members, roles and channels that are relevant.
 * @property OptionRepository $options        Parameters and values from the user.
 * @property string|null      $custom_id      Custom ID the component was created for. Not used for slash commands.
 * @property int|null         $component_type Type of the component. Not used for slash commands.
 * @property string[]|null    $values         Values selected in a select menu.
 * @property string|null      $target_id      Id the of user or message targetted by a user or message command.
 */
class InteractionData extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'name', 'type', 'resolved', 'options', 'custom_id', 'component_type', 'values', 'target_id'];

    /**
     * @inheritdoc
     */
    protected $visible = ['options'];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'options' => OptionRepository::class,
    ];

    /**
     * Sets the options of the interaction.
     *
     * @param array $options
     */
    protected function setOptionsAttribute($options)
    {
        foreach ($options as $option) {
            $this->options->push($this->factory->create(Option::class, $option, true));
        }
    }

    /**
     * Returns a collection of resolved data.
     *
     * @return Resolved|null
     */
    protected function getResolvedAttributes()
    {
        if (empty($this->attributes['resolved'])) {
            return null;
        }

        return $this->factory->create(Resolved::class, $this->attributes['resolved'], true);
    }
}
