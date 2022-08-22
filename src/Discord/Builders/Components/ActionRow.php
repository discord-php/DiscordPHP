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
 * An Action Row is a non-interactive container component for other types of components.
 * It has a type: 1 and a sub-array of components of other types.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#action-rows
 */
class ActionRow extends Component
{
    /**
     * Components contained by the action row.
     *
     * @var Component[]
     */
    private $components = [];

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
     * Adds a component to the action row.
     *
     * @param Component $component Component to add.
     *
     * @throws \InvalidArgumentException
     * @throws \OverflowException
     *
     * @return self
     */
    public function addComponent(Component $component): self
    {
        if ($component instanceof ActionRow) {
            throw new \InvalidArgumentException('You cannot add another `ActionRow` to this action row.');
        }

        if ($component instanceof SelectMenu) {
            throw new \InvalidArgumentException('Cannot add a select menu to an action row.');
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
     * @param Component $component Component to remove.
     *
     * @return self
     */
    public function removeComponent(Component $component): self
    {
        if (($idx = array_search($component, $this->components)) !== null) {
            array_splice($this->components, $idx, 1);
        }

        return $this;
    }

    /**
     * Removes all components from the action row.
     *
     * @return self
     */
    public function clearComponents(): self
    {
        $this->components = [];

        return $this;
    }

    /**
     * Returns all the components in the action row.
     *
     * @return Component[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => Component::TYPE_ACTION_ROW,
            'components' => $this->components,
        ];
    }
}
