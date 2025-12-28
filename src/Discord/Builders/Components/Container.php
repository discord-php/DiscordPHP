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

use Discord\Builders\ComponentsTrait;

/**
 * Containers are a new way to group components together.
 * You can also specify an accent color (similar to embeds) and spoiler it.
 *
 * @link https://discord.com/developers/docs/components/reference#container
 *
 * @since 10.5.0
 *
 * @property int        $type         17 for container component.
 * @property ?int|null  $id           Optional identifier for component.
 * @property array      $components   Components of the type action row, text display, section, media gallery, separator, or file.
 * @property ?int|null  $accent_color Color for the accent on the container as RGB from 0x000000 to 0xFFFFFF.
 * @property ?bool|null $spoiler      Whether the container should be a spoiler (or blurred out). Defaults to false.
 */
class Container extends Layout implements Contracts\ComponentV2
{
    use ComponentsTrait;

    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_CONTAINER;

    /**
     * Accent color for the container.
     *
     * @var int|null
     */
    protected $accent_color;

    /**
     * Whether the container is a spoiler.
     *
     * @var bool|null
     */
    protected $spoiler;

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
    public function addComponent($component): self
    {
        if ($component instanceof Interactive) {
            $component = ActionRow::new()->addComponent($component);
        }

        if (! ($component instanceof ActionRow || $component instanceof Section || $component instanceof TextDisplay || $component instanceof MediaGallery || $component instanceof File || $component instanceof Separator)) {
            throw new \InvalidArgumentException('Invalid component type.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Sets the accent color for the container.
     *
     * @param mixed $color Color code for the container.
     *
     * @return $this
     */
    public function setAccentColor($color = null): self
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
     * @param bool|null $spoiler Whether the container is a spoiler.
     *
     * @return $this
     */
    public function setSpoiler(?bool $spoiler = true): self
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
        return $this->spoiler ?? false;
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

        if (isset($this->accent_color)) {
            $content['accent_color'] = $this->accent_color;
        }

        if (isset($this->spoiler)) {
            $content['spoiler'] = $this->spoiler;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
