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
 * Text inputs are an interactive component that render on modals. They can be used to collect short-form or long-form text.
 *
 * @link https://discord.com/developers/docs/components/reference#text-inputs
 *
 * @since 7.0.0
 *
 * @property int          $type        4 for a text input.
 * @property string       $custom_id   Developer-defined identifier for the input; max 100 characters.
 * @property int          $style       The Text Input Style.
 * @property ?int|null    $min_length  Minimum input length for a text input; min 0, max 4000.
 * @property ?int|null    $max_length  Maximum input length for a text input; min 1, max 4000.
 * @property ?bool|null   $required    Whether this component is required to be filled (defaults to true).
 * @property ?string|null $value       Pre-filled value for this component; max 4000 characters.
 * @property ?string|null $placeholder Custom placeholder text if the input is empty; max 100 characters.
 */
class TextInput extends Interactive
{
    public const USAGE = ['Message', 'Modal'];

    /** Single-line input. */
    public const STYLE_SHORT = 1;
    /** Multi-line input. */
    public const STYLE_PARAGRAPH = 2;

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_TEXT_INPUT;

    /**
     * Style of text input.
     *
     * @var int
     */
    protected $style;

    /**
     * Label for the text input.
     *
     * @deprecated Use a top-level `ComponentObject::Label`
     *
     * @var string|null
     */
    protected $label;

    /**
     * Minimum input length for a text input, min 0, max 4000.
     *
     * @var int|null
     */
    protected $min_length;

    /**
     * Maximum input length for a text input, min 1, max 4000.
     *
     * @var int|null
     */
    protected $max_length;

    /**
     * Whether the text input is required.
     *
     * @var bool|null
     */
    protected $required;

    /**
     * Pre-filled value for text input. Max 4000 characters.
     *
     * @var string|null
     */
    protected $value;

    /**
     * Placeholder string to display if text input is empty. Maximum 100 characters.
     *
     * @var string|null
     */
    protected $placeholder;

    /**
     * Creates a new text input.
     *
     * @param string|null $label     (Deprecated) The label of the text input.
     * @param int         $style     The style of the text input.
     * @param string|null $custom_id The custom ID of the text input. If not given, a UUID will be used
     */
    public function __construct(?string $label = null, int $style = self::STYLE_SHORT, ?string $custom_id = null)
    {
        $this->setLabel($label);
        $this->setStyle($style);
        $this->setCustomId($custom_id ?? self::generateUuid());
    }

    /**
     * Creates a new text input.
     *
     * @param string|null $label     (Deprecated) The label of the text input.
     * @param int         $style     The style of the text input.
     * @param string|null $custom_id The custom ID of the text input.
     *
     * @return self
     */
    public static function new(?string $label = null, int $style = self::STYLE_SHORT, ?string $custom_id = null): self
    {
        return new self($label, $style, $custom_id);
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
     * @deprecated Use a top-level `ComponentObject::Label`
     *
     * @param string|null $label Label of the text input. Maximum 45 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setLabel(?string $label = null): self
    {
        if (isset($label) && poly_strlen($label) > 45) {
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
     * Set if this component is required to be filled (defaults to true).
     *
     * @param bool|null $required
     *
     * @return $this
     */
    public function setRequired(?bool $required = null): self
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
     * Returns whether the text input is disabled.
     *
     * @return bool|null
     */
    public function isRequired(): ?bool
    {
        return $this->required;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
            'style' => $this->style,
        ];

        if (isset($this->label)) {
            $content['label'] = $this->label;
        }

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

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }
}
