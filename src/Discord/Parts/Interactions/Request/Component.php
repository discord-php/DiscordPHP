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

namespace Discord\Parts\Interactions\Request;

use Discord\Builders\Components\Component as ComponentBuilder;
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/**
 * Represents a component received with a message or interaction.
 *
 * @todo split per type
 *
 * @link https://discord.com/developers/docs/interactions/message-components#component-object
 *
 * @since 7.0.0
 *
 * @property int                                    $type        Component type.
 * @property string|null                            $custom_id   Developer-defined identifier for the component; max 100 characters. (Buttons, Select Menus)
 * @property bool|null                              $disabled    Whether the component is disabled; defaults to `false`. (Buttons, Select Menus)
 * @property int|null                               $style       A button style. (Buttons)
 * @property string|null                            $label       Text that appears on the button; max 80 characters. (Buttons)
 * @property Emoji|null                             $emoji       Name, id, and animated. (Buttons)
 * @property string|null                            $url         URL for link-style buttons. (Buttons)
 * @property object[]|null                          $options     The choices in the select; max 25. (Select Menus)
 * @property string|null                            $placeholder Custom placeholder text if nothing is selected; max 150 characters. (Select Menus, Text Inputs)
 * @property int|null                               $min_values  The minimum number of items that must be chosen; default 1, min 0, max 25. (Select Menus)
 * @property int|null                               $max_values  The maximum number of items that can be chosen; default 1, max 25. (Select Menus)
 * @property ExCollectionInterface|Component[]|null $components  A list of child components. (Action Rows)
 * @property int|null                               $min_length  Minimum input length for a text input. (Text Inputs)
 * @property int|null                               $max_length  Maximum input length for a text input. (Text Inputs)
 * @property bool|null                              $required    Whether this component is required to be filled; defaults to `true` (Text Inputs)
 * @property string|null                            $value       Value for this component. (Text Inputs)
 */
class Component extends Part
{
    /**
     * {@inheritDoc}
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
     * Gets the sub-components of the component.
     *
     * @return ExCollectionInterface|ComponentBuilder[]|null $components
     */
    protected function getComponentsAttribute(): ?ExCollectionInterface
    {
        $allowed = [
            ComponentBuilder::TYPE_ACTION_ROW,
            ComponentBuilder::TYPE_SECTION,
            ComponentBuilder::TYPE_TEXT_DISPLAY,
            ComponentBuilder::TYPE_MEDIA_GALLERY,
            ComponentBuilder::TYPE_FILE,
            ComponentBuilder::TYPE_SEPARATOR,
            ComponentBuilder::TYPE_CONTAINER,
            ComponentBuilder::TYPE_ACTION_ROW,
        ];

        if (! isset($this->attributes['components']) && ! in_array($this->type, $allowed)) {
            return null;
        }

        $components = Collection::for(ComponentBuilder::class, null);

        foreach ($this->attributes['components'] ?? [] as $component) {
            $componentType = (is_object($component)) ? (isset($component->type) ? $component->type : 0) : ($component['type'] ?? 0);
            $components->pushItem(
                isset(ComponentBuilder::TYPE_CLASSES[$componentType])
                    ? (new (ComponentBuilder::TYPE_CLASSES[$componentType]))->fill((array) $component)
                    : (new ComponentBuilder())->fill((array) $component),
                $component
            );
        }

        return $components;
    }

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if (! isset($this->attributes['emoji'])) {
            return null;
        }

        return $this->factory->part(Emoji::class, (array) $this->attributes['emoji'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [];
    }
}
