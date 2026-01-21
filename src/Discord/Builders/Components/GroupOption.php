<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Components;

use function Discord\poly_strlen;

/**
 * List of options to render within a Group.
 *
 * @since 10.46.0
 *
 * @property string       $value       Dev-defined value of the option; max 100 characters.
 * @property string       $label       User-facing label of the option; max 100 characters.
 * @property ?string|null $description Optional description for the option; max 100 characters.
 * @property ?bool|null   $default     Shows the option as selected by default.
 */
class GroupOption extends Component
{
    /**
     * Developer value for the option. Maximum 100 characters.
     *
     * @var string
     */
    protected $value;

    /**
     * User-visible label of the option. Maximum 25 characters.
     *
     * @var string
     */
    protected $label;

    /**
     * Description for the option. Maximum 50 characters.
     *
     * @var string|null
     */
    protected $description;

    /**
     * Whether the option should be enabled as default.
     *
     * @var bool|null
     */
    protected $default;

    public function __construct(string $value, string $label)
    {
        $this->setValue($value);
        $this->setLabel($label);
    }

    /**
     * Sets the value text.
     *
     * @param string $label The text for the value. Must be between 1 and 100 characters.
     *
     * @return self
     */
    public function setValue(string $value): self
    {
        if (poly_strlen($value) === 0 || poly_strlen($value) > 100) {
            throw new \LengthException('Value must be between 1 and 100 in length.');
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Gets the value text.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Sets the label text.
     *
     * @param string $label The text for the option. Must be between 1 and 100 characters.
     *
     * @return self
     */
    public function setLabel(string $label): self
    {
        if (poly_strlen($label) === 0 || poly_strlen($label) > 100) {
            throw new \LengthException('Label must be between 1 and 100 in length.');
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Gets the label text.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Sets the description text.
     *
     * @param string|null $description The description for the option. Max 100 characters.
     *
     * @return self
     */
    public function setDescription(?string $description = null): self
    {
        if (isset($description)) {
            if (poly_strlen($description) === 0) {
                $description = null;
            } elseif (poly_strlen($description) > 100) {
                throw new \LengthException('Description must be between 0 and 100 in length.');
            }
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Gets the description text.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    /**
     * Sets the option as default. Pass false to set as non-default.
     *
     * @param bool|null $default
     *
     * @return $this
     */
    public function setDefault(?bool $default = true): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Gets whether the option is set as default.
     *
     * @return bool|null
     */
    public function getDefault(): ?bool
    {
        return $this->default ?? null;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'label' => $this->label,
            'value' => $this->value,
        ];

        if (isset($this->description)) {
            $content['description'] = $this->description;
        }

        if (isset($this->default)) {
            $content['default'] = $this->default;
        }

        return $content;
    }
}
