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
 * A Label is a top-level component.
 *
 * @link https://discord.com/developers/docs/components/reference#label
 *
 * @todo Update to match Discord's documentation upon public release.
 * @todo Update Label class to extend the relevant base class.
 * @todo Confirm if Label will be usable in Message components.
 *
 * @since 10.19.0
 *
 * @property int                    $type        18 for label component.
 * @property string                 $label       The text for the label.
 * @property string|null            $description Optional description for the label.
 * @property StringSelect|TextInput $component   The component associated with the label.
 */
class Label extends ComponentObject
{
    public const USAGE = ['Modal'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_LABEL;

    /**
     * The text for the label.
     *
     * @var string
     */
    protected $label;

    /**
     * Optional description for the label.
     *
     * @var string|null
     */
    protected $description;

    /**
     * The component associated with the label.
     *
     * @var StringSelect|TextInput
     */
    protected $component;

    /**
     * Creates a new label component.
     *
     * @param string                 $label       The text for the label.
     * @param StringSelect|TextInput $component   The component associated with the label.
     * @param string|null            $description Optional description for the label.
     *
     * @return self
     */
    public static function new(string $label, StringSelect|TextInput $component, ?string $description = null): self
    {
        $label_component = new self();

        $label_component->setLabel($label);
        $label_component->setComponent($component);
        $label_component->setDescription($description);

        return $label_component;
    }

    /**
     * Sets the label text.
     *
     * @param string $label The text for the label.
     *
     * @return self
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Sets the description text.
     *
     * @param string|null $description The description for the label.
     *
     * @return self
     */
    public function setDescription(string|null $description): self
    {
        $this->description = $description;

        return $this;
    }

    /** Sets the component associated with the label.
     *
     * @param StringSelect|TextInput $component The component associated with the label.
     *
     * @return self
     */
    public function setComponent(StringSelect|TextInput $component): self
    {
        $this->component = $component;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $data = [
            'type' => $this->type,
            'label' => $this->label,
            'component' => $this->component,
        ];

        if (isset($this->description)) {
            $data['description'] = $this->description;
        }

        return $data;
    }
}
