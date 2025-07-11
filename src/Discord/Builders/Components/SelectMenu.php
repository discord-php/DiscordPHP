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

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use React\EventLoop\TimerInterface;
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
 * @since 10.9.0 Extends Interactive instead of Component
 */
abstract class SelectMenu extends Interactive
{
    public const USAGE = ['Message'];

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = Component::TYPE_SELECT_MENU; // Default type

    /**
     * Custom ID to identify the select menu.
     *
     * @var string
     */
    protected $custom_id;

    /**
     * Specified choices in a select menu (only required and available for string selects (type 3); max 25.
     *
     * @var array|null
     */
    protected $options;

    /**
     * List of channel types to include in the channel select component (type 8).
     *
     * @var array|null
     */
    protected $channel_types;

    /**
     * Placeholder string to display if nothing is selected. Maximum 150 characters.
     *
     * @var string|null
     */
    protected $placeholder;

    /**
     * List of default values for auto-populated select menu components;
     * number of default values must be in the range defined by min_values and max_values.
     *
     * @var array|null
     */
    protected $default_values;

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
     * @param string|null $custom_id The custom ID of the select menu. If not given, a UUID will be used
     */
    public function __construct(?string $custom_id)
    {
        $this->setCustomId($custom_id ?? self::generateUuid());
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
     * Sets the type for the select menu.
     * (text: 3, user: 5, role: 6, mentionable: 7, channels: 8).
     *
     * @param int $type
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function setType(int $type): self
    {
        static $allowed_types = [self::TYPE_STRING_SELECT, self::TYPE_USER_SELECT, self::TYPE_ROLE_SELECT, self::TYPE_MENTIONABLE_SELECT, self::TYPE_CHANNEL_SELECT];
        if (! in_array($type, $allowed_types)) {
            throw new \InvalidArgumentException('Invalid select menu type.');
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Sets the custom ID for the select menu.
     *
     * @param string $custom_id
     *
     * @throws \LengthException If the custom ID is longer than 100 characters.
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
     * Specified choices in a select menu (only required and available for string selects (type 3); max 25.
     *
     * @param array $options
     *
     * @throws \InvalidArgumentException If the select menu type is not `TYPE_STRING_SELECT`.
     *
     * @return $this
     */
    public function setOptions(array $options): self
    {
        if ($this->type != self::TYPE_STRING_SELECT) {
            throw new \InvalidArgumentException('Options can only be set for string selects.');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * Sets the channel types for the select menu.
     *
     * This method is only applicable if the select menu type is `TYPE_CHANNEL_SELECT`.
     * If the select menu type is not `TYPE_CHANNEL_SELECT`, an `InvalidArgumentException` will be thrown.
     *
     * @param array $channel_types
     *
     * @throws \InvalidArgumentException If the select menu type is not `TYPE_CHANNEL_SELECT`.
     *
     * @return $this
     */
    public function setChannelTypes(array $channel_types): self
    {
        if ($this->type != self::TYPE_CHANNEL_SELECT) {
            throw new \InvalidArgumentException('Channel types can only be set for channel selects.');
        }

        $this->channel_types = $channel_types;

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

    public function setDefaultValues(?array $default_values): self
    {
        static $allowed_types = [self::TYPE_USER_SELECT, self::TYPE_ROLE_SELECT, self::TYPE_MENTIONABLE_SELECT, self::TYPE_CHANNEL_SELECT];
        if (! in_array($this->type, $allowed_types)) {
            throw new \InvalidArgumentException('Default values can only be set for user, role, mentionable, and channel selects.');
        }
        $this->default_values = $default_values;

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
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false, int|float|null $timeout = null): self
    {
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback == null) {
            return $this;
        }

        $this->listener = $this->createListener($callback, $oneOff, $timeout);

        $discord->on(Event::INTERACTION_CREATE, $this->listener);

        return $this;
    }

    /**
     * Creates a listener callback for handling select menu interactions.
     *
     * @param callable       $callback The callback to execute when the interaction is received.
     *                                 If the select menu has options, the callback receives
     *                                 ($interaction, $options), otherwise just ($interaction).
     * @param bool           $oneOff   Whether the listener should be removed after being triggered once.
     * @param int|float|null $timeout  Optional timeout in seconds after which the listener will be removed.
     *
     * @return callable The listener closure to be registered for interaction events.
     */
    protected function createListener(callable $callback, bool $oneOff = false, int|float|null $timeout = null): callable
    {
        $timer = null;

        $listener = function (Interaction $interaction) use ($callback, $oneOff, &$timer) {
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
                $ack = static fn () => $interaction->isResponded() ?: $interaction->acknowledge();

                if ($response instanceof PromiseInterface) {
                    $response->then($ack);
                } else {
                    $ack();
                }

                if ($oneOff) {
                    $this->removeListener();
                }

                /** @var ?TimerInterface $timer */
                if ($timer) {
                    $this->discord->getLoop()->cancelTimer($timer);
                }
            }
        };

        if ($timeout) {
            $timer = $this->discord->getLoop()->addTimer($timeout, fn () => $this->discord->removeListener(Event::INTERACTION_CREATE, $listener));
        }

        return $listener;
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
     * Returns the options of the select menu.
     *
     * @return array|null
     */
    public function getOptions(): ?array
    {
        return $this->options;
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
     * Returns the channel types of the select menu.
     *
     * @return array|null
     */
    public function getChannelTypes(): ?array
    {
        return $this->channel_types;
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
     * Returns the default values of the select menu.
     *
     * @return array|null
     */
    public function getDefaultValues(): ?array
    {
        return $this->default_values;
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
     * Returns whether the select menu is disabled.
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

        if (isset($this->default_values)) {
            $content['default_values'] = $this->default_values;
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

        return $content;
    }
}
