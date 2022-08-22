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

use Discord\Parts\Guild\Emoji;

use function Discord\poly_strlen;

/**
 * Option for select menu component.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#select-menu-object-select-option-structure
 */
class Option extends Component
{
    /**
     * User-visible label of the option. Maximum 25 characters.
     *
     * @var string
     */
    private $label;

    /**
     * Developer value for the option. Maximum 100 characters.
     *
     * @var string
     */
    private $value;

    /**
     * Description for the option. Maximum 50 characters.
     *
     * @var string|null
     */
    private $description;

    /**
     * Emoji to display alongside the option.
     *
     * @var array|null
     */
    private $emoji;

    /**
     * Whether the option should be enabled as default.
     *
     * @var bool
     */
    private $default = false;

    /**
     * Creates a new select menu option.
     *
     * @param string      $label User-visible label for the option. Maximum 100 characters.
     * @param string|null $value Developer value for the option. Maximum 100 characters. Leave as null to automatically generate a UUID.
     *
     * @throws \LengthException
     */
    public function __construct(string $label, ?string $value)
    {
        if (poly_strlen($label) > 100) {
            throw new \LengthException('Label must be less than or equal to 100 characters.');
        }

        if (isset($value) && poly_strlen($value) > 100) {
            throw new \LengthException('Value must be less than or equal to 100 characters.');
        }

        $this->label = $label;
        $this->value = $value ?? $this->generateUuid();
    }

    /**
     * Creates a new select menu option.
     *
     * @param string      $label User-visible label for the option. Maximum 25 characters.
     * @param string|null $value Developer value for the option. Maximum 100 characters. Leave as null to automatically generate a UUID.
     *
     * @return self
     */
    public static function new(string $label, ?string $value = null): self
    {
        return new self($label, $value);
    }

    /**
     * Sets the description of the option. Null to clear.
     *
     * @param string|null $description Description of the option. Maximum 100 characters.
     *
     * @throws \LengthException
     *
     * @return self
     */
    public function setDescription(?string $description): self
    {
        if (isset($description) && poly_strlen($description) > 100) {
            throw new \LengthException('Description must be less than or equal to 100 characters.');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Sets the emoji of the option. Null to clear.
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
     * Sets the option as default. Pass false to set as non-default.
     *
     * @param bool $default
     *
     * @return self
     */
    public function setDefault(bool $default = true): self
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Returns the developer value for the option.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Returns the user-visible label for the option.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Returns the description for the option.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Returns the emoji display alongside the option.
     *
     * @return array|null
     */
    public function getEmoji(): ?array
    {
        return $this->emoji;
    }

    /**
     * Returns whether the option is default.
     *
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->default;
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

        if (isset($this->emoji)) {
            $content['emoji'] = $this->emoji;
        }

        if ($this->default) {
            $content['default'] = true;
        }

        return $content;
    }
}
