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
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

/**
 * Select menus are interactive components that allow users to select one or
 * more options from a dropdown list in messages.
 * On desktop, clicking on a select menu opens a dropdown-style UI.
 * On mobile, tapping a select menu opens up a half-sheet with the options.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#select-menus
 *
 * @since 10.0.0 Renamed from SelectMenu to StringSelect and made SelectMenu abstract
 */
abstract class SelectMenu extends Component
{
    /**
     * Custom ID to identify the select menu.
     *
     * @var string
     */
    protected $custom_id;

    /**
     * Placeholder string to display if nothing is selected. Maximum 150 characters.
     *
     * @var string|null
     */
    protected $placeholder;

    /**
     * Minimum number of options that must be selected.
     * Default 1, minimum 0, maximum 25.
     *
     * @var int|null
     */
    protected $min_values;

    /**
     * Maximum number of options that must be selected.
     * Default 1, maximum 25.
     *
     * @var int|null
     */
    protected $max_values;

    /**
     * Whether the select menu should be disabled.
     *
     * @var bool|null
     */
    protected $disabled;

    /**
     * Callback used to listen for `INTERACTION_CREATE` events.
     *
     * @var callable|null
     */
    protected $listener;

    /**
     * Discord instance when the listener is set.
     *
     * @var Discord|null
     */
    protected $discord;

    /**
     * Creates a new select menu.
     *
     * @param string|null $custom_id The custom ID of the select menu. If not given, an UUID will be used
     */
    public function __construct(?string $custom_id)
    {
        $this->setCustomId($custom_id ?? $this->generateUuid());
    }

    /**
     * Creates a new select menu.
     *
     * @param string|null $custom_id The custom ID of the select menu.
     *
     * @return static
     */
    public static function new(?string $custom_id = null): self
    {
        return new static($custom_id);
    }

    /**
     * Sets the custom ID for the select menu.
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
     * Sets the placeholder string to display if nothing is selected.
     *
     * @param string|null $placeholder Maximum 150 characters. `null` to clear placeholder.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setPlaceholder(?string $placeholder): self
    {
        if (isset($placeholder) && poly_strlen($placeholder) > 150) {
            throw new \LengthException('Placeholder string must be less than or equal to 150 characters.');
        }

        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * Sets the minimum number of options which must be chosen.
     *
     * @param int|null $min_values Default `1`, minimum `0` and maximum `25`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMinValues(?int $min_values): self
    {
        if (isset($min_values) && ($min_values < 0 || $min_values > 25)) {
            throw new \LengthException('Number must be between 0 and 25 inclusive.');
        }

        $this->min_values = $min_values;

        return $this;
    }

    /**
     * Sets the maximum number of options which must be chosen.
     *
     * @param int|null $max_values Default `1` and maximum `25`. `null` to set as default.
     *
     * @throws \LengthException
     *
     * @return $this
     */
    public function setMaxValues(?int $max_values): self
    {
        if ($max_values && $max_values > 25) {
            throw new \LengthException('Number must be less than or equal to 25.');
        }

        $this->max_values = $max_values;

        return $this;
    }

    /**
     * Sets the select menus disabled state.
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Sets the callable listener for the select menu. The `$callback` function
     * will be called when the selection of the menu is changed.
     *
     * The callback function is called with the `Interaction` object as well as
     * a `Collection` of selected options.
     *
     * If you do not respond to or acknowledge the `Interaction`, it will be
     * acknowledged for you.
     * Note that if you intend to respond to or acknowledge the interaction
     * inside a promise, you should return a promise that resolves *after* you
     * respond or acknowledge.
     *
     * The callback will only be called once with the `$oneOff` parameter set to
     * true.
     * This can be changed to false, and the callback will be called each time
     * the selection is changed. To remove the listener, you can pass
     * `$callback` as null.
     *
     * The select menu listener will not persist when the bot restarts.
     *
     * @param callable $callback Callback to call when the selection is changed. Will be called with the interaction object and collection of options.
     * @param Discord  $discord  Discord client.
     * @param bool     $oneOff   Whether the listener should be removed after the selection is changed for the first time.
     *
     * @return $this
     *
     * @todo setListener callback return for each type.
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false): self
    {
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = function (Interaction $interaction) use ($callback, $oneOff) {
            if ($interaction->data->component_type == $this->type &&
                $interaction->data->custom_id == $this->custom_id) {
                if (empty($this->options)) {
                    $response = $callback($interaction);
                } else {
                    $options = Collection::for(Option::class, null);

                    foreach ($this->options as $option) {
                        if (in_array($option->getValue(), $interaction->data->values)) {
                            $options->pushItem($option);
                        }
                    }

                    $response = $callback($interaction, $options);
                }
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
     * Removes the listener from the button.
     *
     * @return $this
     */
    public function removeListener(): self
    {
        return $this->setListener(null, $this->discord);
    }

    /**
     * Returns the Custom ID of the select menu.
     *
     * @return string
     */
    public function getCustomId(): string
    {
        return $this->custom_id;
    }

    /**
     * Returns the placeholder string of the select menu.
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
    public function getMinValues(): ?int
    {
        return $this->min_values;
    }

    /**
     * Returns the maximum number of options that must be selected.
     *
     * @return int|null
     */
    public function getMaxValues(): ?int
    {
        return $this->max_values;
    }

    /**
     * Returns wether the select menu is disabled.
     *
     * @return bool|null
     */
    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'custom_id' => $this->custom_id,
        ];

        if (isset($this->options)) {
            $content['options'] = $this->options;
        }

        if (isset($this->channel_types)) {
            $content['channel_types'] = $this->channel_types;
        }

        if (isset($this->placeholder)) {
            $content['placeholder'] = $this->placeholder;
        }

        if (isset($this->min_values)) {
            if (isset($this->options) && $this->min_values > count($this->options)) {
                throw new \OutOfBoundsException('There are less options than the minimum number of options to be selected.');
            }

            $content['min_values'] = $this->min_values;
        }

        if ($this->max_values) {
            if (isset($this->options) && $this->max_values > count($this->options)) {
                throw new \OutOfBoundsException('There are less options than the maximum number of options to be selected.');
            }

            $content['max_values'] = $this->max_values;
        }

        if ($this->disabled) {
            $content['disabled'] = true;
        }

        return [
            'type' => Component::TYPE_ACTION_ROW,
            'components' => [$content],
        ];
    }
}
