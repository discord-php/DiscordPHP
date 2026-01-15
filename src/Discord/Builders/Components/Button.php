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

use Discord\Discord;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use React\EventLoop\TimerInterface;
use React\Promise\PromiseInterface;

use function Discord\poly_strlen;

/**
 * Buttons are interactive components that render in messages.
 * They can be clicked by users, and send an interaction to your app when
 * clicked.
 *
 * @link https://discord.com/developers/docs/components/reference#buttons
 *
 * @since 7.0.0
 *
 * @property int          $type      2 for a button.
 * @property ?int|null    $id        Optional identifier for component.
 * @property int          $style     A button style.
 * @property ?string|null $label     Text that appears on the button; max 80 characters.
 * @property ?array|null  $emoji     Partial emoji: name, id, and animated.
 * @property string|null  $custom_id Developer-defined identifier for the button; max 100 characters.
 * @property ?string|null $sku_id    Identifier for a purchasable SKU, only available when using premium-style buttons.
 * @property ?string|null $url       URL for link-style buttons; max 512 characters.
 * @property ?bool|null   $disabled  Whether the button is disabled (defaults to false).
 */
class Button extends Interactive
{
    public const USAGE = ['Message'];

    /** The most important or recommended action in a group of options. */
    public const STYLE_PRIMARY = 1;
    /** Alternative or supporting actions. */
    public const STYLE_SECONDARY = 2;
    /**	Positive confirmation or completion actions. */
    public const STYLE_SUCCESS = 3;
    /** An action with irreversible consequences. */
    public const STYLE_DANGER = 4;
    /** Navigates to a URL. */
    public const STYLE_LINK = 5;
    /** Purchase. */
    public const STYLE_PREMIUM = 6;

    /**
     * Component type.
     *
     * @var int
     */
    protected $type = ComponentObject::TYPE_BUTTON;

    /**
     * Style of button.
     *
     * @var int
     */
    protected $style = 1;

    /**
     * Label for the button.
     *
     * @var string|null
     */
    protected $label;

    /**
     * Emoji to display on the button.
     *
     * @var array|null
     */
    protected $emoji;

    /**
     * 	Identifier for a purchasable SKU, only available when using premium-style buttons.
     *
     * @var string|null
     */
    protected $sku_id;

    /**
     * URL to send as the button. Only for link buttons.
     *
     * @var string|null
     */
    protected $url;

    /**
     * Whether the button is disabled.
     *
     * @var bool|null
     */
    protected $disabled;

    /**
     * Listener for when the button is pressed.
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
     * Creates a new button.
     *
     * @param int         $style     Style of the button.
     * @param string|null $custom_id custom ID of the button. If not given, a UUID will be used
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
            self::STYLE_PREMIUM,
        ])) {
            throw new \InvalidArgumentException('Invalid button style.');
        }

        $this->style = $style;
        if (in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
        ])) {
            $this->setCustomId($custom_id ?? self::generateUuid());
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
     * Creates a new primary button.
     *
     * @param string|null $custom_id Custom ID of the button.
     *
     * @return self
     */
    public static function primary(?string $custom_id = null)
    {
        $button = new self(self::STYLE_PRIMARY);

        if (! isset($custom_id)) {
            $custom_id = self::generateUuid();
        }

        return $button->setCustomId($custom_id);
    }

    /**
     * Creates a new secondary button.
     *
     * @param string|null $custom_id Custom ID of the button.
     *
     * @return self
     */
    public static function secondary(?string $custom_id = null)
    {
        $button = new self(self::STYLE_SECONDARY);

        if (! isset($custom_id)) {
            $custom_id = self::generateUuid();
        }

        return $button->setCustomId($custom_id);
    }

    /**
     * Creates a new success button.
     *
     * @param string|null $custom_id Custom ID of the button.
     *
     * @return self
     */
    public static function success(?string $custom_id = null)
    {
        $button = new self(self::STYLE_SUCCESS);

        if (! isset($custom_id)) {
            $custom_id = self::generateUuid();
        }

        return $button->setCustomId($custom_id);
    }

    /**
     * Creates a new danger button.
     *
     * @param string|null $custom_id Custom ID of the button.
     *
     * @return self
     */
    public static function danger(?string $custom_id = null)
    {
        $button = new self(self::STYLE_DANGER);

        if (! isset($custom_id)) {
            $custom_id = self::generateUuid();
        }

        return $button->setCustomId($custom_id);
    }

    /**
     * Creates a new link button.
     *
     * @param string $url
     *
     * @return self
     */
    public static function link(string $url): self
    {
        $button = new self(self::STYLE_LINK);

        $button->setUrl($url);

        return $button;
    }

    /**
     * Creates a new premium button.
     *
     * @param string $sku_id
     *
     * @return self
     */
    public static function premium(string $sku_id): self
    {
        $button = new self(self::STYLE_PREMIUM);

        $button->setSkuId($sku_id);

        return $button;
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
     * @return $this
     */
    public function setStyle(int $style): self
    {
        if (! in_array($style, [
            self::STYLE_PRIMARY,
            self::STYLE_SECONDARY,
            self::STYLE_SUCCESS,
            self::STYLE_DANGER,
            self::STYLE_LINK,
            self::STYLE_PREMIUM,
        ])) {
            throw new \InvalidArgumentException('Invalid button style.');
        }

        if ($this->style === self::STYLE_LINK && $style !== self::STYLE_LINK) {
            $this->url = null;
        } elseif ($this->style !== self::STYLE_LINK && $style === self::STYLE_LINK && $this->listener && $this->discord) {
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
     * @return $this
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
     * Sets the emoji of the button.
     *
     * @param Emoji|string|null $emoji Emoji to set. `null` to clear.
     *
     * @return $this
     */
    public function setEmoji($emoji): self
    {
        $this->emoji = (function ($emoji) {
            if (! $emoji) {
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
                'animated' => $animated === 'a',
            ];
        })($emoji);

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
     * @return $this
     */
    public function setCustomId(?string $custom_id): self
    {
        if ($this->style === Button::STYLE_LINK || $this->style === Button::STYLE_PREMIUM) {
            throw new \LogicException('You cannot set the custom ID of a link or premium button.');
        }

        if (isset($custom_id) && poly_strlen($custom_id) > 100) {
            throw new \LengthException('Custom ID must be maximum 100 characters.');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets the SKU ID for the button. Only valid for premium buttons.
     *
     * @param string|null $sku_id
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function setSkuId(?string $sku_id): self
    {
        if ($this->style !== Button::STYLE_PREMIUM) {
            throw new \LogicException('You cannot set the SKU ID of a non-premium button.');
        }

        $this->sku_id = $sku_id;

        return $this;
    }

    /**
     * Sets the URL of the button. Only valid for link buttons.
     *
     * @param string|null $url
     *
     * @throws \LogicException
     * @throws \LengthException URL exceeds 512 characters.
     *
     * @return $this
     */
    public function setUrl(?string $url): self
    {
        if ($this->style !== Button::STYLE_LINK) {
            throw new \LogicException('You cannot set the URL of a non-link button.');
        }

        if (isset($url) && poly_strlen($url) > 512) {
            throw new \LengthException('URL cannot exceed 512 characters.');
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Sets the button as disabled/not disabled.
     *
     * @param bool|null $disabled
     *
     * @return $this
     */
    public function setDisabled(?bool $disabled): self
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
     * @param ?callable      $callback Callback to call when the button is pressed. Will be called with the interaction object.
     * @param Discord        $discord  Discord client.
     * @param bool           $oneOff   Whether the listener should be removed after the button is pressed for the first time.
     * @param int|float|null $timeout  Optional timeout in seconds after which the listener will be removed.
     *
     * @throws \LogicException
     *
     * @return $this
     */
    public function setListener(?callable $callback, Discord $discord, bool $oneOff = false, int|float|null $timeout = null): self
    {
        if ($this->style === Button::STYLE_LINK || $this->style === Button::STYLE_PREMIUM) {
            throw new \LogicException('You cannot add a listener to a link or premium button.');
        }

        if (! isset($this->custom_id)) {
            $this->custom_id = self::generateUuid();
        }

        // Remove any existing listener
        if ($this->listener) {
            $this->discord->removeListener(Event::INTERACTION_CREATE, $this->listener);
        }

        $this->discord = $discord;

        if ($callback === null) {
            return $this;
        }

        $this->listener = $this->createListener($callback, $oneOff, $timeout);

        $discord->on(Event::INTERACTION_CREATE, $this->listener);

        return $this;
    }

    /**
     * Creates a listener.
     *
     * @param callable       $callback The callback to execute when the interaction occurs.
     * @param bool           $oneOff   Whether the listener should be removed after one use.
     * @param int|float|null $timeout  Optional timeout in seconds after which the listener will be removed.
     *
     * @return callable The listener closure.
     */
    protected function createListener(callable $callback, bool $oneOff = false, int|float|null $timeout = null): callable
    {
        $timer = null;

        $listener = function (Interaction $interaction) use ($callback, $oneOff, &$timer) {
            if ($interaction->data->component_type !== ComponentObject::TYPE_BUTTON || $interaction->data->custom_id !== $this->custom_id) {
                return;
            }

            $response = $callback($interaction);
            $ack = static fn () => $interaction->isResponded() ?: $interaction->acknowledge();

            $response instanceof PromiseInterface
                ? $response->then($ack)
                : $ack();

            if ($oneOff) {
                $this->removeListener();
            }

            /** @var ?TimerInterface $timer */
            if ($timer) {
                $this->discord->getLoop()->cancelTimer($timer);
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
     * Returns the SKU ID for the button. Only for premium buttons.
     *
     * @return string|null
     */
    public function getSkuId(): ?string
    {
        return $this->sku_id;
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
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $content = [
            'type' => $this->type,
            'style' => $this->style,
        ];

        if ($this->style !== Button::STYLE_PREMIUM) {
            if (! isset($this->label)) {
                throw new \DomainException('Non-Premium buttons must have a `label` field set.');
            }
            $content['label'] = $this->label;

            if (isset($this->emoji)) {
                $content['emoji'] = $this->emoji;
            }

            if (isset($this->custom_id)) {
                $content['custom_id'] = $this->custom_id;
            } elseif ($this->style !== Button::STYLE_LINK) {
                throw new \DomainException('Buttons must have a `custom_id` field set.');
            }

            if ($this->style === Button::STYLE_LINK) {
                if (! isset($this->url)) {
                    throw new \DomainException('Link buttons must have a `url` field set.');
                }
                $content['url'] = $this->url;
            }
        }

        if ($this->style === Button::STYLE_PREMIUM) {
            if (! isset($this->sku_id)) {
                throw new \DomainException('Premium buttons must have a `sku_id` field set.');
            }
            $content['sku_id'] = $this->sku_id;
        }

        if (isset($this->disabled)) {
            $content['disabled'] = $this->disabled;
        }

        if (isset($this->id)) {
            $content['id'] = $this->id;
        }

        return $content;
    }

    public function __debugInfo(): array
    {
        $vars = get_object_vars($this);
        $vars['class'] = $this::class;
        unset($vars['discord']);
        if (isset($vars['listener'])) {
            $vars['listener'] = 'object(Closure)';
        }

        return $vars;
    }
}
