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
 * An Action Row is a non-interactive container component for other types of
 * components.
 * It has a type: 1 and a sub-array of components of other types.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#action-rows
 *
 * @since 7.0.0
 *
 * @property int               $type       1 for action row component.
 * @property ComponentObject[] $components Up to 5 interactive button components or a single select component.
 */
class ActionRow extends Layout
{
    /** Usage of ActionRow in Modal is deprecated. Use `Component::Label` as the top-level container. */
    public const USAGE = ['Message', 'Modal'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_ACTION_ROW;

    /**
     * Components contained by the action row.
     *
     * @var ComponentObject[]
     */
    protected $components = [];

    /**
     * Creates a new action row.
     *
     * @return self
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Add a group of components to the action row.
     *
     * @since 10.42.0
     *
     * @param ComponentObject[] $components Components to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
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
     * Add a group of components to the action row.
     *
     * @param ComponentObject[] $components Components to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        If the action row has more than 5 components.
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
     * Adds a component to the action row.
     *
     * @param ComponentObject $component Component to add.
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        If the action row has more than 5 components.
     *
     * @since 10.19.0
     *
     * @return $this
     */
    public function addComponent(ComponentObject $component): self
    {
        if ($component instanceof ActionRow) {
            throw new \InvalidArgumentException('You cannot add another `ActionRow` to this action row.');
        }

        if ($component instanceof SelectMenu) {
            foreach ($this->components as $existingComponent) {
                if ($existingComponent instanceof SelectMenu) {
                    throw new \InvalidArgumentException('You cannot add more than one select menu to an action row.');
                }
            }
        }

        if (count($this->components) >= 5) {
            throw new \OverflowException('You can only have 5 components per action row.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Removes a component from the action row.
     *
     * @param ComponentObject $component Component to remove.
     *
     * @return $this
     */
    public function removeComponent(ComponentObject $component): self
    {
        if (($idx = array_search($component, $this->components)) !== false) {
            array_splice($this->components, $idx, 1);
        }

        return $this;
    }

    /**
     * Removes all components from the action row.
     *
     * @return $this
     */
    public function clearComponents(): self
    {
        $this->components = [];

        return $this;
    }

    /**
     * Returns all the components in the action row.
     *
     * @return ComponentObject[]
     */
    public function getComponents(): array
    {
        return $this->components;
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

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
