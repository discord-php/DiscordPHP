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
use Discord\Repository\Interaction\ComponentRepository;
use Discord\Repository\Interaction\OptionRepository;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-data
 *
 * @since 7.0.0
 *
 * @property string              $id             ID of the invoked command.
 * @property string              $name           Name of the invoked command.
 * @property int                 $type           The type of the invoked command.
 * @property Resolved|null       $resolved       Resolved users, members, roles and channels that are relevant.
 * @property OptionRepository    $options        Parameters and values from the user.
 * @property string|null         $guild_id       ID of the guild internally passed from Interaction or ID of the guild the command belongs to.
 * @property string|null         $target_id      Id the of user or message targetted by a user or message command.
 * @property string|null         $custom_id      Custom ID the component was created for. (Only for Message Component & Modal)
 * @property int|null            $component_type Type of the component. (Only for Message Component)
 * @property string[]|null       $values         Values selected in a select menu. (Only for Message Component)
 * @property ComponentRepository $components     The values submitted by the user. (Only for Modal)
 */
class InteractionData extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'type',
        'resolved',
        'options',
        'guild_id',
        'target_id',

        // message components
        'custom_id',
        'component_type',
        'values',
        'components', // modal only
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'options' => OptionRepository::class,
        'components' => ComponentRepository::class,
    ];

    /**
     * Sets the options of the interaction.
     *
     * @param array $options
     */
    protected function setOptionsAttribute($options)
    {
        foreach ($options as $option) {
            $this->options->pushItem($this->factory->part(Option::class, (array) $option, true));
        }
    }

    /**
     * Sets the components of the interaction.
     *
     * @param array $components
     */
    protected function setComponentsAttribute($components)
    {
        foreach ($components as $component) {
            $this->components->pushItem($this->factory->part(Component::class, (array) $component, true));
        }
    }

    /**
     * Returns a collection of resolved data.
     *
     * @return Resolved|null
     */
    protected function getResolvedAttribute(): ?Resolved
    {
        if (! isset($this->attributes['resolved'])) {
            return null;
        }

        $adata = $this->attributes['resolved'];
        if (isset($this->attributes['guild_id'])) {
            $adata->guild_id = $this->guild_id;
        }

        return $this->factory->part(Resolved::class, (array) $adata, true);
    }
}
