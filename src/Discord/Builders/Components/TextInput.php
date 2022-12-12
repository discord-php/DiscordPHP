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

use function Discord\poly_strlen;

/**
 * Text inputs are an interactive component that render on modals. They can be used to collect short-form or long-form text.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#text-inputs
 *
 * @since 7.0.0
 */
class TextInput extends Component
{
    public const STYLE_SHORT = 1;
    public const STYLE_PARAGRAPH = 2;

    /**
     * Custom ID to identify the text input.
     *
     * @var string
     */
    private $custom_id;

    /**
     * Style of text input.
     *
     * @var int
     */
    private $style;

    /**
     * Label for the text input.
     *
     * @var string
     */
    private $label;

    /**
     * Minimum input length for a text input, min 0, max 4000.
     *
     * @var int|null
     */
    private $min_length;

    /**
     * Maximum input length for a text input, min 1, max 4000.
     *
     * @var int|null
     */
    private $max_length;

    /**
     * Whether the text input is required.
     *
     * @var bool
     */
    private $required;

    /**
     * Pre-filled value for text input. Max 4000 characters.
     *
     * @var string|null
     */
    private $value;

    /**
     * Placeholder string to display if text input is empty. Maximum 100 characters.
     *
     * @var string|null
     */
    private $placeholder;

    /**
     * Creates a new text input.
     *
     * @param string      $label     The label of the text input.
     * @param int         $style     The style of the text input.
     * @param string|null $custom_id The custom ID of the text input. If not given, an UUID will be used
     */
    public function __construct(string $label, int $style, ?string $custom_id = null)
    {
        $this->setLabel($label);
        $this->setStyle($style);
        $this->setCustomId($custom_id ?? $this->generateUuid());
    }

    /**
     * Creates a new text input.
     *
     * @param string      $label     The label of the text input.
     * @param int         $style     The style of the text input.
     * @param string|null $custom_id The custom ID of the text input.
     *
     * @return self
     */
    public static function new(string $label, int $style, ?string $custom_id = null): self
    {
        return new self($label, $style, $custom_id);
    }

    /**
     * Sets the custom ID for the text input.
     *
     * @param string $custom_id
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setCustomId($custom_id): self
    {
        if (poly_strlen($custom_id) > 100) {
            throw new \LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets the style of the text input.
     *
     * @param int $style
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setStyle(int $style): self
    {
        if ($style < 1 || $style > 2) {
            throw new \InvalidArgumentException('Invalid text input style.');
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Sets the label of the text input.
     *
     * @param string $label Label of the text input. Maximum 45 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setLabel(string $label): self
    {
        if (poly_strlen($label) > 45) {
            throw new \LengthException('Label must be maximum 45 characters.');
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Sets the minimum input length for a text input.
     *
     * @param int|null $min_length Minimum `0` and maximum `4000`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMinLength(?int $min_length): self
    {
        if (isset($min_length) && ($min_length < 0 || $min_length > 4000)) {
            throw new \LengthException('Length must be between 0 and 4000 inclusive.');
        }

        $this->min_length = $min_length;

        return $this;
    }

    /**
     * Sets the maximum input length for a text input.
     *
     * @param int|null $max_length Minimum `1` and maximum `4000`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMaxLength(?int $max_length): self
    {
        if (isset($max_length) && ($max_length < 1 || $max_length > 4000)) {
            throw new \LengthException('Length must be between 1 and 4000 inclusive.');
        }

        $this->max_length = $max_length;

        return $this;
    }

    /**
     * Sets the placeholder string to display if text input is empty.
     *
     * @param string|null $placeholder Maximum 100 characters. `null` to clear placeholder.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        if (isset($placeholder) && poly_strlen($placeholder) > 100) {
            throw new \LengthException('Placeholder string must be less than or equal to 100 characters.');
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Set if this component is required to be filled, default false.
     *
     * @param bool $required
     *
     * @return $this
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * Sets a pre-filled value for the text input.
     *
     * @param string|null $value A pre-filled value, max 4000 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setValue(?string $value): self
    {
        if (isset($value) && poly_strlen($value) > 4000) {
            throw new \LengthException('Pre-filled value must be maximum 4000 characters.');
        }

        $this->value = $value;

        return $this;
    }

    /**
     * Returns the Custom ID of the text input.
     *
     * @return string
     */
    public function getCustomId(): string
    {
        return $this->custom_id;
    }

    /**
     * Returns the placeholder string of the text input.
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    /**
     * Returns the minimum length of the text input.
     *
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return $this->min_length;
    }

    /**
     * Returns the maximum length of the text input.
     *
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->max_length;
    }

    /**
     * Returns wether the text input is disabled.
     *
     * @return bool|null
     */
    public function isRequired(): ?bool
    {
        return $this->required;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => Component::TYPE_TEXT_INPUT,
            'custom_id' => $this->custom_id,
            'style' => $this->style,
            'label' => $this->label,
        ];

        if (isset($this->min_length)) {
            $content['min_length'] = $this->min_length;
        }

        if (isset($this->max_length)) {
            if (isset($this->min_length) && $this->min_length > $this->max_length) {
                throw new \OutOfRangeException('Minimum length cannot be higher than maximum length');
            }

            $content['max_length'] = $this->max_length;
        }

        if (isset($this->required)) {
            $content['required'] = $this->required;
        }

        if (isset($this->value)) {
            $content['value'] = $this->value;
        }

        if (isset($this->placeholder)) {
            $content['placeholder'] = $this->placeholder;
        }

        return $content;
    }
}
