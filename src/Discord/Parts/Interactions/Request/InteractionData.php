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
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Part;

/**
 * Represents the data associated with an interaction.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-object-interaction-data
 *
 * @since 7.0.0
 *
 * @property string                                 $id             ID of the invoked command.
 * @property string                                 $name           Name of the invoked command.
 * @property int                                    $type           The type of the invoked command.
 * @property Resolved|null                          $resolved       Resolved users, members, roles and channels that are relevant.
 * @property ExCollectionInterface|Option[]|null    $options        Parameters and values from the user.
 * @property string|null                            $guild_id       ID of the guild internally passed from Interaction or ID of the guild the command belongs to.
 * @property string|null                            $target_id      ID the of user or message targeted by a user or message command.
 * @property string|null                            $custom_id      Custom ID the component was created for. (Only for Message Component & Modal)
 * @property int|null                               $component_type Type of the component. (Only for Message Component)
 * @property string[]|null                          $values         Values selected in a select menu. (Only for Message Component)
 * @property ExCollectionInterface|Component[]|null $components     The values submitted by the user. (Only for Modal)
 */
class InteractionData extends Part
{
    /**
     * {@inheritDoc}
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
     * Gets the options of the interaction.
     *
     * @return ExCollectionInterface|Option[]|null $options
     */
    protected function getOptionsAttribute(): ?ExCollectionInterface
    {
        if (! isset($this->attributes['options']) && $this->type != Command::CHAT_INPUT) {
            return null;
        }

        $options = Collection::for(Option::class, 'name');

        foreach ($this->attributes['options'] ?? [] as $option) {
            $options->pushItem($this->createOf(Option::class, $option));
        }

        return $options;
    }

    /**
     * Gets the components of the interaction.
     *
     * @return ExCollectionInterface|Component[]|null $components
     */
    protected function getComponentsAttribute(): ?ExCollectionInterface
    {
        if (! isset($this->attributes['components'])) {
            return null;
        }

        $components = Collection::for(Component::class, null);

        foreach ($this->attributes['components'] as $component) {
            $components->pushItem($this->createOf(Component::class, $component));
        }

        return $components;
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

        return $this->createOf(Resolved::class, $adata);
    }
}
