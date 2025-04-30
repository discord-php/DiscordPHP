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

use Discord\Builders\Components\ActionRow;
use Discord\Builders\Components\Section;
use Discord\Builders\Components\TextDisplay;
use Discord\Builders\Components\MediaGallery;
use Discord\Builders\Components\File;
use Discord\Builders\Components\Separator;

/**
 * Containers are a new way to group components together.
 * You can also specify an accent color (similar to embeds) and spoiler it.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#container
 *
 * @since 10.5.0
 */
class Container extends Component implements Contracts\ComponentV2
{
    /**
     * Array of components.
     *
     * @var Component[]
     */
    private $components = [];

    /**
     * Accent color for the container.
     *
     * @var int|null
     */
    private $accent_color;

    /**
     * Whether the container is a spoiler.
     *
     * @var bool
     */
    private $spoiler = false;

    /**
     * Creates a new container.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Adds a component to the container.
     *
     * @param ActionRow|Section|TextDisplay|MediaGallery|File|Separator $component Component to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        Container exceeds 10 components.
     *
     * @return $this
     */
    public function addComponent(Component $component): self
    {
        if (! ( $component instanceof ActionRow || $component instanceof Section || $component instanceof TextDisplay || $component instanceof MediaGallery || $component instanceof File || $component instanceof Separator )) {
            throw new \InvalidArgumentException('Invalid component type.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Add a group of components to the container.
     *
     * @param Component[] $components Components to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     *
     * @return $this
     */
    public function addComponents(array $components): self
    {
        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Sets the components for the container.
     *
     * @param Component[] $components Components to set.
     *
     * @return $this
     */
    public function setComponents(array $components): self
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Sets the accent color for the container.
     *
     * @param int|null $color Color code for the container.
     *
     * @return $this
     */
    public function setAccentColor(?int $color): self
    {
        $this->accent_color = $color;

        return $this;
    }

    /**
     * Sets whether the container is a spoiler.
     *
     * @param bool $spoiler Whether the container is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(bool $spoiler = true): self
    {
        $this->spoiler = $spoiler;

        return $this;
    }

    /**
     * Returns all the components in the container.
     *
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Returns the accent color for the container.
     *
     * @return int|null
     */
    public function getAccentColor(): ?int
    {
        return $this->accent_color;
    }

    /**
     * Returns whether the container is a spoiler.
     *
     * @return bool
     */
    public function isSpoiler(): bool
    {
        return $this->spoiler;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => Component::TYPE_CONTAINER,
            'components' => $this->components,
        ];

        if (isset($this->accent_color)) {
            $data['accent_color'] = $this->accent_color;
        }

        if ($this->spoiler) {
            $data['spoiler'] = true;
        }

        return $data;
    }
}
