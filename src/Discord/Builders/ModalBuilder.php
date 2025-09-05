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
use Discord\Helpers\Collection;
use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Interactions\Interaction;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Helper class used to build messages.
 *
 * @link https://discord.com/developers/docs/components/using-modal-components
 *
 * @since 10.19.0
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class ModalBuilder extends Builder implements JsonSerializable
{
    /**
     * Interaction type.
     *
     * @var int
     */
    protected $type = Interaction::RESPONSE_TYPE_MODAL;

    /**
     * The title of the popup modal, max 45 characters.
     *
     * @var string
     */
    protected $title;

    /**
     * Developer-defined identifier for the component, max 100 characters.
     *
     * @var string
     */
    protected $custom_id;

    /**
     * Between 1 and 5 (inclusive) components that make up the modal.
     *
     * @var ExCollectionInterface<ComponentObject>|ComponentObject[]
     */
    protected $components;

    /**
     * Creates a new message builder.
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
     * Sets the components of the modal.
     *
     * @param ExCollectionInterface<ComponentObject>|ComponentObject[] $components
     *
     * @return $this
     */
    public function setComponents($components): self
    {
        $this->components = Collection::for(ComponentObject::class);

        foreach ($components as $component) {
            $this->components->pushItem($component);
        }

        return $this;
    }

    /**
     * Add a component to the modal.
     *
     * Only ActionRow, TextDisplay, and Label components are allowed.
     *
     * Using ActionRow in modals is now deprecated. Use `Component::Label` as the top level component.
     *
     * @param ComponentObject $component
     *
     * @return $this
     */
    public function addComponent($component): self
    {
        $this->components ??= Collection::for(ComponentObject::class);

        $this->components->pushItem($component);

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
        if (isset($this->components)) {
            if (($idx = $this->components->search($component)) !== false) {
                $this->components->splice($idx, 1);
            }
        }

        return $this;
    }

    /**
     * Returns the components of the modal.
     *
     * @return ExCollectionInterface<ComponentObject>|ComponentObject[]
     */
    public function getComponents(): ExCollectionInterface
    {
        return $this->components ?? Collection::for(ComponentObject::class);
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
