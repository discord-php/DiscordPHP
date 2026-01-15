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
use Discord\Builders\Components\TextInput;
use Discord\Parts\Interactions\Interaction;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build Modals.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#interaction-response-object-modal
 * @link https://discord.com/developers/docs/components/using-modal-components
 *
 * @since 10.19.0
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class ModalBuilder extends Builder implements JsonSerializable
{
    use ComponentsTrait;

    /**
     * Interaction type.
     *
     * @var int
     */
    protected $type = Interaction::RESPONSE_TYPE_MODAL;

    /**
     * Developer-defined identifier for the component, max 100 characters.
     *
     * @var string
     */
    protected $custom_id;

    /**
     * The title of the popup modal, max 45 characters.
     *
     * @var string
     */
    protected $title;

    /**
     * Creates a new message builder.
     *
     * @param string            $title
     * @param string            $custom_id
     * @param ComponentObject[] $components
     *
     * @return static
     */
    public static function new($title, $custom_id, $components): self
    {
        $modal = new self();

        $modal->setTitle($title);
        $modal->setCustomId($custom_id);
        $modal->setComponents($components);

        return $modal;
    }

    /**
     * Sets the custom ID of the modal.
     *
     * @param string $custom_id
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setCustomId(string $custom_id): self
    {
        if (poly_strlen($custom_id) > 100) {
            throw new \LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Returns the custom ID of the modal.
     *
     * @return string
     */
    public function getCustomId(): string
    {
        return $this->custom_id;
    }

    /**
     * Set the title of the modal.
     *
     * @param string $title Maximum length is 45 characters.
     *
     * @throws \LengthException Modal title too long.
     *
     * @return $this
     */
    public function setTitle(string $title): self
    {
        if (poly_strlen($title) > 45) {
            throw new \LengthException('Modal title can not be longer than 45 characters');
        }

        $this->title = $title;

        return $this;
    }

    /**
     * Returns the title of the modal.
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Add a component to the modal.
     *
     * Only ActionRow, TextDisplay, and Label components are allowed.
     *
     * Using ActionRow in modals is now deprecated. Use `ComponentObject::Label` as the top level component.
     *
     * @param ComponentObject $component
     *
     * @throws \InvalidArgumentException Component is not a valid type.
     * @throws \OverflowException        If the modal has more than 5 components.
     *
     * @return $this
     */
    public function addComponent($component): self
    {
        if (! in_array('Modal', $component::USAGE, true)) {
            throw new \InvalidArgumentException('Invalid component type for modals.');
        }

        if ($component instanceof TextInput) {
            $this->discord->logger->warning('Discord no longer recommends using Text Input within an Action Row in modals. Going forward all Text Inputs should be placed inside a Label component.');
        }

        $this->components ??= [];

        if (count($this->components) >= 5) {
            throw new \OverflowException('You can only have 5 components per modal.');
        }

        $this->components[] = $component;

        return $this;
    }

    /**
     * Removes a component from the builder.
     *
     * @param ComponentObject $component Component to remove.
     *
     * @return $this
     */
    public function removeComponent($component): self
    {
        $this->components ??= [];

        $index = array_search($component, $this->components, true);
        if ($index !== false) {
            array_splice($this->components, $index, 1);
        }

        return $this;
    }

    /**
     * Returns the components of the modal.
     *
     * @return ComponentObject[]
     */
    public function getComponents(): array
    {
        return $this->components ?? [];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'data' => [
                'custom_id' => $this->custom_id,
                'title' => $this->title,
                'components' => $this->components,
            ],
        ];
    }
}
