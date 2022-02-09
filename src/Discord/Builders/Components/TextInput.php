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

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

/**
 * Text inputs are an interactive component that render on modals. They can be used to collect short-form or long-form text.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#text-inputs
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
    private $style = 1;

    /**
     * Label for the text input.
     *
     * @var string
     */
    private $label;

    /**
     * Minimum input length for a text input, min 0, max 4000
     *
     * @var int|null
     */
    private $min_length;

    /**
     * Maximum input length for a text input, min 1, max 4000
     *
     * @var int|null
     */
    private $max_length;

    /**
     * Whether the text input is required.
     *
     * @var bool
     */
    private $required = false;

    /**
     * Pre-filled value for text input. Max 4000 characters
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
     * Callback used to listen for `INTERACTION_CREATE` events.
     *
     * @var callable|null
     */
    private $listener;

    /**
     * Discord instance when the listener is set.
     *
     * @var Discord|null
     */
    private $discord;

    /**
     * Creates a new text input.
     *
     * @param string|null $custom_id The custom ID of the text input. If not given, an UUID will be used
     */
    public function __construct(?string $custom_id)
    {
        $this->setCustomId($custom_id ?? $this->generateUuid());
    }

    /**
     * Creates a new text input.
     *
     * @param string|null $custom_id The custom ID of the text input.
     *
     * @return self
     */
    public static function new(?string $custom_id = null): self
    {
        return new self($custom_id);
    }

    /**
     * Sets the custom ID for the text input
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
     * Sets the style of the text button.
     *
     * @param int $style
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setStyle(int $style): self
    {
        if (! in_array($style, [self::STYLE_SHORT, self::STYLE_PARAGRAPH])) {
            throw new \InvalidArgumentException('Invalid text input style.');
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Sets the label of the text input.
     *
     * @param string $label Label of the text input. Maximum 80 characters.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setLabel(string $label): self
    {
        if (poly_strlen($label) > 80) {
            throw new \LengthException('Label must be maximum 80 characters.');
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Sets the minimum input length for a text input.
     * Minimum 0 and maximum 4000. Null to set as default.
     *
     * @param int|null $min_length
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
     * Minimum 1 and maximum 4000. Null to set as default.
     *
     * @param int|null $max_length
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
     * Sets the placeholder string to display if nothing is selected.
     * Maximum 100 characters. Null to clear placeholder.
     *
     * @param string|null $placeholder
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        if (isset($placeholder) && strlen($placeholder) > 100) {
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
     * Sets the callable listener for the text input. The `$callback` will be called when submitted.
     *
     * If you do not respond to or acknowledge the `Interaction`, it will be acknowledged for you.
     * Note that if you intend to respond to or acknowledge the interaction inside a promise, you should
     * return a promise that resolves *after* you respond or acknowledge.
     *
     * The callback will only be called once with the `$oneOff` parameter set to true.
     * This can be changed to false, and the callback will be called each time the text input is submitted.
     * To remove the listener, you can pass `$callback` as null.
     *
     * The text input listener will not persist when the bot restarts.
     *
     * @param callable $callback Callback to call when the text input is submitted. Will be called with the interaction object.
     * @param Discord  $discord  Discord client.
     * @param bool     $oneOff   Whether the listener should be removed after the text input is submitted for the first time.
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false): self
    {
        if (! isset($this->custom_id)) {
            $this->custom_id = $this->generateUuid();
        }

        // Remove any existing listener
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = function (Interaction $interaction) use ($callback, $oneOff) {
            if ($interaction->data->component_type == Component::TYPE_TEXT_INPUT && $interaction->data->custom_id == $this->custom_id) {
                $response = $callback($interaction);
                $ack = function () use ($interaction) {
                    // attempt to acknowledge interaction if it has not already been responded to.
                    try {
                        $interaction->acknowledge();
                    } catch (\Exception $e) {
                    }
                };

                if ($response instanceof PromiseInterface) {
                    $response->then($ack);
                } else {
                    $ack();
                }

                if ($oneOff) {
                    $this->removeListener();
                }
            }
        };

        $discord->on(Event::INTERACTION_CREATE, $this->listener);

        return $this;
    }

    /**
     * Removes the listener from the text input.
     *
     * @return $this
     */
    public function removeListener(): self
    {
        return $this->setListener(null, $this->discord);
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
     * Returns the minimum number of options that must be selected.
     *
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return $this->min_length;
    }

    /**
     * Returns the maximum number of options that must be selected.
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
     * @inheritDoc
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
                throw new \OutOfBoundsException('Minimum length cannot be higher than maximum length');
            }

            $content['max_length'] = $this->max_length;
        }

        if ($this->required) {
            $content['required'] = true;
        }

        if (isset($value)) {
            $content['value'] = $this->value;
        }

        if (isset($this->placeholder)) {
            $content['placeholder'] = $this->placeholder;
        }

        return $content;
    }
}
