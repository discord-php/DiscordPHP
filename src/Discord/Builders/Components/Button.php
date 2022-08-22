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
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

/**
 * Buttons are interactive components that render on messages.
 * They can be clicked by users, and send an interaction to your app when clicked.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#buttons
 */
class Button extends Component
{
    public const STYLE_PRIMARY = 1;
    public const STYLE_SECONDARY = 2;
    public const STYLE_SUCCESS = 3;
    public const STYLE_DANGER = 4;
    public const STYLE_LINK = 5;

    /**
     * Style of button.
     *
     * @var int
     */
    private $style = 1;

    /**
     * Label for the button.
     *
     * @var string|null
     */
    private $label;

    /**
     * Emoji to display on the button.
     *
     * @var array|null
     */
    private $emoji;

    /**
     * Custom ID to send with the button.
     *
     * @var string|null
     */
    private $custom_id;

    /**
     * URL to send as the button. Only for link buttons.
     *
     * @var string|null
     */
    private $url;

    /**
     * Whether the button is disabled.
     *
     * @var bool
     */
    private $disabled = false;

    /**
     * Listener for when the button is pressed.
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
     * Creates a new button.
     *
     * @param int         $style     Style of the button.
     * @param string|null $custom_id custom ID of the button. If not given, an UUID will be used
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(int $style, ?string $custom_id = null)
    {
        if (! in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
            self::STYLE_LINK,
        ])) {
            throw new \InvalidArgumentException('Invalid button style.');
        }

        $this->style = $style;
        if ($this->style != self::STYLE_LINK) {
            $this->setCustomId($custom_id ?? $this->generateUuid());
        }
    }

    /**
     * Creates a new button.
     *
     * @param int         $style     Style of the button.
     * @param string|null $custom_id custom ID of the button.
     *
     * @return self
     */
    public static function new(int $style, ?string $custom_id = null): self
    {
        return new self($style, $custom_id);
    }

    /**
     * Sets the style of the button.
     *
     * If the button is originally a link button, the link attribute will be cleared.
     * If the button was changed to a link button, the listener will be cleared.
     *
     * @param int $style
     *
     * @throws \InvalidArgumentException
     *
     * @return self
     */
    public function setStyle(int $style): self
    {
        if (! in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
            self::STYLE_LINK,
        ])) {
            throw new \InvalidArgumentException('Invalid button style.');
        }

        if ($this->style == self::STYLE_LINK && $style != self::STYLE_LINK) {
            $this->url = null;
        } elseif ($this->style != self::STYLE_LINK && $style == self::STYLE_LINK && $this->listener && $this->discord) {
            $this->setListener(null, $this->discord);
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Sets the label of the button.
     *
     * @param string|null $label Label of the button. Maximum 80 characters.
     *
     * @throws \LengthException
     *
     * @return self
     */
    public function setLabel(?string $label): self
    {
        if (isset($label) && poly_strlen($label) > 80) {
            throw new \LengthException('Label must be maximum 80 characters.');
        }

        $this->label = $label;

        return $this;
    }

    /**
     * Sets the emoji of the button. Null to clear.
     *
     * @param Emoji|string|null $emoji Emoji to set.
     *
     * @return self
     */
    public function setEmoji($emoji): self
    {
        $this->emoji = (function () use ($emoji) {
            if ($emoji === null) {
                return null;
            }

            if ($emoji instanceof Emoji) {
                return [
                    'id' => $emoji->id,
                    'name' => $emoji->name,
                    'animated' => $emoji->animated,
                ];
            }

            $parts = explode(':', $emoji, 3);

            if (count($parts) < 3) {
                return [
                    'id' => null,
                    'name' => $emoji,
                    'animated' => false,
                ];
            }

            [$animated, $name, $id] = $parts;

            return [
                'id' => $id,
                'name' => $name,
                'animated' => $animated == 'a',
            ];
        })();

        return $this;
    }

    /**
     * Sets the custom ID of the button.
     *
     * @param string|null $custom_id
     *
     * @throws \LogicException
     * @throws \LengthException
     *
     * @return self
     */
    public function setCustomId(?string $custom_id): self
    {
        if ($this->style == Button::STYLE_LINK) {
            throw new \LogicException('You cannot set the custom ID of a link button.');
        }

        if (isset($custom_id) && poly_strlen($custom_id) > 100) {
            throw new \LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets the URL of the button. Only valid for link buttons.tatic.
     *
     * @param string|null $url
     *
     * @throws \LogicException
     *
     * @return self
     */
    public function setUrl(?string $url): self
    {
        if ($this->style != Button::STYLE_LINK) {
            throw new \LogicException('You cannot set the URL of a non-link button.');
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Sets the button as disabled/not disabled.
     *
     * @param bool $disabled
     *
     * @return self
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Sets the callable listener for the button. The `$callback` will be called when the button
     * is pressed.
     *
     * If you do not respond to or acknowledge the `Interaction`, it will be acknowledged for you.
     * Note that if you intend to respond to or acknowledge the interaction inside a promise, you should
     * return a promise that resolves *after* you respond or acknowledge.
     *
     * The callback will only be called once with the `$oneOff` parameter set to true.
     * This can be changed to false, and the callback will be called each time the button is pressed.
     * To remove the listener, you can pass `$callback` as null.
     *
     * The button listener will not persist when the bot restarts.
     *
     * @param callable $callback Callback to call when the button is pressed. Will be called with the interaction object.
     * @param Discord  $discord  Discord client.
     * @param bool     $oneOff   Whether the listener should be removed after the button is pressed for the first time.
     *
     * @throws \LogicException
     *
     * @return self
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false): self
    {
        if ($this->style == Button::STYLE_LINK) {
            throw new \LogicException('You cannot add a listener to a link button.');
        }

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
            if ($interaction->data->component_type == Component::TYPE_BUTTON && $interaction->data->custom_id == $this->custom_id) {
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
     * Removes the listener from the button.
     *
     * @return self
     */
    public function removeListener(): self
    {
        return $this->setListener(null, $this->discord);
    }

    /**
     * Returns the style for the button.
     *
     * @return int
     */
    public function getStyle(): int
    {
        return $this->style;
    }

    /**
     * Returns the label for the button.
     *
     * @return string|null
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Returns the emoji display on the button.
     *
     * @return array|null
     */
    public function getEmoji(): ?array
    {
        return $this->emoji;
    }

    /**
     * Returns the custom ID of the button.
     *
     * @return string|null
     */
    public function getCustomId(): ?string
    {
        return $this->custom_id;
    }

    /**
     * Returns the URL of the button. Only for link buttons.
     *
     * @return string|null
     */
    public function getURL(): ?string
    {
        return $this->url;
    }

    /**
     * Returns whether the button is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => Component::TYPE_BUTTON,
            'style' => $this->style,
        ];

        if (isset($this->label)) {
            $content['label'] = $this->label;
        }

        if (isset($this->emoji)) {
            $content['emoji'] = $this->emoji;
        }

        if (isset($this->custom_id)) {
            $content['custom_id'] = $this->custom_id;
        } elseif ($this->style != Button::STYLE_LINK) {
            throw new \DomainException('Buttons must have a `custom_id` field set.');
        }

        if ($this->style == Button::STYLE_LINK) {
            if (! isset($this->url)) {
                throw new \DomainException('Link buttons must have a `url` field set.');
            }
            $content['url'] = $this->url;
        }

        if ($this->disabled) {
            $content['disabled'] = true;
        }

        return $content;
    }
}
