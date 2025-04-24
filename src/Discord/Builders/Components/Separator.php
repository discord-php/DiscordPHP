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
 * Separator components allow you to divide components with a divider.
 * You can make the divider big or small, and make it invisible if needed.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#separator
 *
 * @since 10.5.0
 */
class Separator extends Component implements Contracts\ComponentV2
{
    /**
     * Spacing size constants.
     */
    public const SPACING_SMALL = 1;
    public const SPACING_LARGE = 2;

    /**
     * Whether the separator is a divider.
     *
     * @var bool
     */
    private $divider = true;

    /**
     * Spacing size for the separator.
     *
     * @var int
     */
    private $spacing = self::SPACING_SMALL;

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
     * @param bool $divider Whether the separator is a divider.
     *
     * @return $this
     */
    public function setDivider(bool $divider = true): self
    {
        $this->divider = $divider;

        return $this;
    }

    /**
     * Sets the spacing size for the separator.
     *
     * @param int $spacing Spacing size for the separator.
     *
     * @throws \InvalidArgumentException Invalid spacing size.
     *
     * @return $this
     */
    public function setSpacing(int $spacing): self
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
        return $this->divider;
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
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => Component::TYPE_SEPARATOR,
        ];

        if (! $this->divider) {
            $data['divider'] = false;
        }

        if ($this->spacing !== self::SPACING_SMALL) {
            $data['spacing'] = $this->spacing;
        }

        return $data;
    }
}
