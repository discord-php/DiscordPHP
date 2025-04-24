<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

/**
 * Section components allow you to define up to 3 text display components and add either a thumbnail or button to the right side.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#section
 *
 * @since 10.5.0
 */
class Section extends Component implements Contracts\ComponentV2
{
    /**
     * Array of text display components.
     *
     * @var TextDisplay[]
     */
    private $components = [];

    /**
     * Accessory component (Thumbnail or Button).
     *
     * @var Thumbnail|Button|null
     */
    private $accessory;

    /**
     * Creates a new section.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Adds a text display component to the section.
     * Text displays can only be used within sections.
     *  Use setAccessory() instead for Thumbnail or Button.
     *
     * @param TextDisplay $component Text display component to add.
     *
     * @throws \InvalidArgumentException Component is not a TextDisplay.
     * @throws \OverflowException Section exceeds 3 text components.
     *
     * @return $this
     */
    public function addComponent(Component $component): self
    {
        if (! ($component instanceof TextDisplay)) {
            throw new \InvalidArgumentException('Section can only contain TextDisplay components.');
        }

        if (count($this->components) >= 3) {
            throw new \OverflowException('You can only have 3 text components per section.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Sets the accessory component of the section.
     * Only Thumbnail or Button components can be used as accessories.
     *
     * @param Thumbnail|Button $component Thumbnail or Button component.
     *
     * @throws \InvalidArgumentException Component is not a Thumbnail or Button.
     *
     * @return $this
     */
    public function setAccessory(Component $component): self
    {
        if (! ($component instanceof Thumbnail || $component instanceof Button)) {
            throw new \InvalidArgumentException('Accessory may only contain Thumbnail or Button component.');
        }
        $this->accessory = $component;

        return $this;
    }

    /**
     * Returns all the text components in the section.
     *
     * @return TextDisplay[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Returns the accessory component.
     *
     * @return Thumbnail|Button|null
     */
    public function getAccessory(): Component
    {
        return $this->accessory;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => Component::TYPE_SECTION,
            'components' => $this->components,
        ];

        if (isset($this->accessory)) {
            $data['accessory'] = $this->accessory;
        }

        return $data;
    }
}
