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

use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Discord\Repository\Interaction\ComponentRepository;

/**
 * Represents a component received with a message or interaction.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#component-object
 *
 * @property int                 $type        Component type.
 * @property string|null         $custom_id   A developer-defined identifier for the component, max 100 characters. (Buttons, Select Menus)
 * @property bool|null           $disabled    Whether the component is disabled, default false. (Buttons, Select Menus)
 * @property int|null            $style       One of button styles. (Buttons)
 * @property string|null         $label       Text that appears on the button, max 80 characters. (Buttons)
 * @property Emoji|null          $emoji       Name, id, and animated. (Buttons)
 * @property string|null         $url         A url for link-style buttons. (Buttons)
 * @property object[]|null       $options     The choices in the select, max 25. (Select Menus)
 * @property string|null         $placeholder Custom placeholder text if nothing is selected, max 150 characters. (Select Menus, Text Inputs)
 * @property int|null            $min_values  The minimum number of items that must be chosen; default 1, min 0, max 25. (Select Menus)
 * @property int|null            $max_values  The maximum number of items that can be chosen; default 1, max 25. (Select Menus)
 * @property ComponentRepository $components  A list of child components. (Action Rows)
 * @property int|null            $min_length  The minimum input length for a text input. (Text Inputs)
 * @property int|null            $max_length  The maximum input length for a text input. (Text Inputs)
 * @property bool|null           $required    Whether this component is required to be filled. (Text Inputs)
 * @property string|null         $value       Value for this component. (Text Inputs)
 */
class Component extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'type',
        'custom_id',
        'disabled',
        'style',
        'label',
        'emoji',
        'url',
        'options',
        'placeholder',
        'min_values',
        'max_values',
        'components',
        'min_length',
        'max_length',
        'required',
        'value',
    ];

    /**
     * @inheritdoc
     */
    protected $repositories = [
        'components' => ComponentRepository::class,
    ];

    /**
     * Sets the sub-components of the component.
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
     * Gets the partial emoji attribute.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
