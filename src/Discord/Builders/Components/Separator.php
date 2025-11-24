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
 * Separator components allow you to divide components with a divider.
 * You can make the divider big or small, and make it invisible if needed.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#separator
 *
 * @since 10.5.0
 *
 * @property int        $type    14 for separator component.
 * @property ?int|null  $id      Optional identifier for component.
 * @property ?bool|null $divider Whether a visual divider should be displayed in the component. Defaults to true.
 * @property ?int|null  $spacing Size of separator padding—1 for small padding, 2 for large padding. Defaults to 1.
 */
class Separator extends Layout implements Contracts\ComponentV2
{
    public const USAGE = ['Message'];

    /**
     * Spacing size constants.
     */
    public const SPACING_SMALL = 1;
    public const SPACING_LARGE = 2;

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_SEPARATOR;

    /**
     * Whether the separator is a divider.
     *
     * @var bool|null
     */
    protected $divider;

    /**
     * Spacing size for the separator.
     *
     * @var int|null
     */
    protected $spacing;

    /**
     * Creates a new separator.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Sets whether the separator is a divider.
     *
     * @param bool|null $divider Whether the separator is a divider.
     *
     * @return $this
     */
    public function setDivider(?bool $divider = true): self
    {
        $this->divider = $divider;

        return $this;
    }

    /**
     * Sets the spacing size for the separator.
     *
     * @param int|null $spacing Spacing size for the separator.
     *
     * @throws \InvalidArgumentException Invalid spacing size.
     *
     * @return $this
     */
    public function setSpacing(?int $spacing = null): self
    {
        if (! in_array($spacing, [self::SPACING_SMALL, self::SPACING_LARGE])) {
            throw new \InvalidArgumentException('Invalid spacing size.');
        }

        $this->spacing = $spacing;

        return $this;
    }

    /**
     * Returns whether the separator is a divider.
     *
     * @return bool
     */
    public function isDivider(): bool
    {
        return $this->divider ?? true;
    }

    /**
     * Returns the spacing size for the separator.
     *
     * @return int
     */
    public function getSpacing(): int
    {
        return $this->spacing;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
        ];

        if (isset($this->divider)) {
            $content['divider'] = $this->divider;
        }

        if (isset($this->spacing)) {
            $content['spacing'] = $this->spacing;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
