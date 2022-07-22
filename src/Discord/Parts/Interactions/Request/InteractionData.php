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
 * @see https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-data-structure
 *
 * @property string              $id             ID of the invoked command.
 * @property string              $name           Name of the invoked command.
 * @property int                 $type           The type of the invoked command.
 * @property Resolved|null       $resolved       Resolved users, members, roles and channels that are relevant.
 * @property OptionRepository    $options        Parameters and values from the user.
 * @property string|null         $custom_id      Custom ID the component was created for. Not used for slash commands.
 * @property int|null            $component_type Type of the component. Not used for slash commands.
 * @property string[]|null       $values         Values selected in a select menu.
 * @property string|null         $target_id      Id the of user or message targetted by a user or message command.
 * @property ComponentRepository $components     The values submitted by the user in modal.
 * @property string|null         $guild_id       ID of the guild internally passed from Interaction or ID of the guild the command belongs to.
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
        'custom_id',
        'component_type',
        'values',
        'target_id',
        'components',
        'guild_id',
    ];

    /**
     * @inheritdoc
     */
    protected $hidden = ['guild_id'];

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
            $this->options->pushItem($this->factory->create(Option::class, $option, true));
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
            $this->components->pushItem($this->factory->create(Component::class, $component, true));
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

        return $this->factory->create(Resolved::class, $adata, true);
    }
}
