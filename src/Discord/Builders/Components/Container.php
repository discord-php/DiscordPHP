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
 * Containers are a new way to group components together.
 * You can also specify an accent color (similar to embeds) and spoiler it.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#container
 *
 * @since 10.5.0
 */
class Container extends Layout implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_CONTAINER;

    /**
     * Array of components.
     *
     * @var ComponentObject[]
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
     * Resolves a color to an integer.
     *
     * @param array|int|string $color
     *
     * @throws \InvalidArgumentException `$color` cannot be resolved
     *
     * @return int
     */
    protected static function resolveColor($color): int
    {
        if (is_numeric($color)) {
            $color = (int) $color;
        }

        if (is_int($color)) {
            return $color;
        }

        if (! is_array($color)) {
            return hexdec((str_replace('#', '', (string) $color)));
        }

        if (count($color) < 1) {
            throw new \InvalidArgumentException('Color "'.var_export($color, true).'" is not resolvable');
        }

        return (($color[0] << 16) + (($color[1] ?? 0) << 8) + ($color[2] ?? 0));
    }

    /**
     * Adds a component to the container.
     *
     * @param ActionRow|SelectMenu|Section|TextDisplay|MediaGallery|File|Separator $component Component to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     *
     * @return $this
     */
    public function addComponent(ComponentObject $component): self
    {
        if ($component instanceof SelectMenu) {
            $component = ActionRow::new()->addComponent($component);
        }

        if (! ( $component instanceof ActionRow || $component instanceof Section || $component instanceof TextDisplay || $component instanceof MediaGallery || $component instanceof File || $component instanceof Separator )) {
            throw new \InvalidArgumentException('Invalid component type.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Add a group of components to the container.
     *
     * @param ComponentObject[] $components Components to add.
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
     * @param ComponentObject[] $components Components to set.
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
     * @param mixed $color Color code for the container.
     *
     * @return $this
     */
    public function setAccentColor($color): self
    {
        if ($color !== null) {
            $color = self::resolveColor($color);
        }

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
     * @return ComponentObject[]
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
            'type' => $this->type,
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
