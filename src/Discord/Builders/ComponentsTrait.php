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

namespace Discord\Builders;

use Discord\Builders\Components\ComponentObject;

/**
 * Trait for builders that use components.
 *
 * @since 10.42.0
 */
trait ComponentsTrait
{
    /**
     * Components of the builder.
     *
     * @var ComponentObject[]
     */
    protected $components = [];

    /**
     * Sets the components of the builder.
     *
     * @param ComponentObject[]|null $components Components to set.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        Builder exceeds component limits.
     *
     * @return $this
     */
    public function setComponents($components = null)
    {
        $this->components = [];

        return $components ? $this->addComponents($components) : $this;
    }

    /**
     * Adds the components to the builder.
     *
     * @param ComponentObject[] $components Components to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        Builder exceeds component limits.
     *
     * @return $this
     */
    public function addComponents($components)
    {
        foreach ($components as $component) {
            $this->addComponent($component);
        }

        return $this;
    }

    /**
     * Adds a component to the builder.
     *
     * @param ComponentObject $component Component to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        Builder exceeds component limits.
     *
     * @return $this
     */
    abstract public function addComponent($component);
}
