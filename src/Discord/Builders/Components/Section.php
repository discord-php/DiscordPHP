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

namespace Discord\Builders\Components;

/**
 * Section components allow you to define up to 3 text display components and add either a thumbnail or button to the right side.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#section
 *
 * @since 10.5.0
 *
 * @property int              $type       9 for a section component.
 * @property ?int|null        $id         Optional identifier for the component.
 * @property TextDisplay[]    $components One to three text display components.
 * @property Thumbnail|Button $accessory  A thumbnail or button component.
 */
class Section extends Layout implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_SECTION;

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
     * Sets a group of components to the section.
     *
     * @since 10.42.0
     *
     * @param TextDisplay[]|string[] $components Components to set.
     *
     * @throws \InvalidArgumentException Component is not a TextDisplay.
     * @throws \OverflowException        Section exceeds 3 text components.
     *
     * @return $this
     */
    public function setComponents($components): self
    {
        $this->components = [];

        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Add a group of components to the section.
     *
     * @since 10.19.0
     *
     * @param TextDisplay[]|string[] $components Components to add.
     *
     * @throws \InvalidArgumentException Component is not a TextDisplay.
     * @throws \OverflowException        Section exceeds 3 text components.
     *
     * @return $this
     */
    public function addComponents($components): self
    {
        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Adds a text display component to the section.
     * Text displays can only be used within sections.
     * Use setAccessory() instead for Thumbnail or Button.
     *
     * @param TextDisplay|string $component Text display component to add.
     *
     * @throws \InvalidArgumentException Component is not a TextDisplay.
     * @throws \OverflowException        Section exceeds 3 text components.
     *
     * @return $this
     */
    public function addComponent(ComponentObject|string $component): self
    {
        if (is_string($component)) {
            $component = TextDisplay::new($component);
        }

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
    public function setAccessory(ComponentObject $component): self
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
    public function getAccessory(): ComponentObject
    {
        return $this->accessory;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'components' => $this->components,
        ];

        if (! isset($this->accessory)) {
            throw new \DomainException('Section must have an accessory component set.');
        }
        $content['accessory'] = $this->accessory;

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
