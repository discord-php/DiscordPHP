<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel\Message;

use Discord\Builders\Components\ComponentObject;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * Components allow you to style and structure your messages, modals, and interactions.
 * They are interactive elements that can create rich user experiences in your Discord applications.
 *
 * Components are a field on the message object and modal.
 * You can use them when creating messages or responding to an interaction, like an application command.
 *
 * @link https://discord.com/developers/docs/components/reference#what-is-a-component
 *
 * @since 10.11.0
 *
 * @property int         $type The type of the component.
 * @property string|null $id   32 bit integer used as an optional identifier for component.
 */
class Component extends Part
{
    /**
     * Available components and their respective classes.
     *
     * @var array<int, string>
     */
    public const TYPES = ComponentObject::TYPES;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'id',
    ];

    /**
     * Gets the components.
     *
     * @return ExCollectionInterface<Component>|Component[]
     */
    protected function getComponentsAttribute(): ExCollectionInterface
    {
        return $this->attributeTypedCollectionHelper(Component::class, 'components');
    }

    /**
     * Gets the component.
     */
    public function getComponentAttribute(): ?Component
    {
        return $this->attributePartHelper('component', Component::TYPES[$this->attributes['component']->type ?? 0]);
    }
}
