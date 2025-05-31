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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Guild\Emoji;
use JsonSerializable;

/**
 * Specified choices in a string select menu; max 25
 *
 * @link https://discord.com/developers/docs/components/reference#string-select-select-option-structure
 *
 * @since 10.11.0
 *
 * @property string      $label       User-facing name of the option; max 100 characters.
 * @property string      $value       Dev-defined value of the option; max 100 characters.
 * @property string|null $description Additional description of the option; max 100 characters.
 * @property array|null  $emoji       Partial emoji object: id, name, and animated
 * @property bool|null   $default     Will show this option as selected by default.
 */
class StringSelectOption implements JsonSerializable
{
    /** @var string */
    protected $label;

    /** @var string */
    protected $value;

    /** @var string|null */
    protected $description = null;

    /** @var array|null */
    protected $emoji = null;

    /** @var bool|null */
    protected $default = null;

    public function __construct(
        string $label,
        string $value,
        ?string $description = null,
        Emoji|array|null $emoji = null,
        ?bool $default = null
    ) {
        $this->label = $label;
        $this->value = $value;
        $this->description = $description;
        $this->setEmoji($emoji);
        $this->default = $default;
    }

    public static function new(
        string $label,
        string $value,
        ?string $description = null,
        Emoji|array|null $emoji = null,
        ?bool $default = null
    ): self {
        return new self($label, $value, $description, $emoji, $default);
    }

    public function setEmoji(Emoji|array|null $emoji): void
    {
        if ($emoji instanceof Emoji) {
            $partial = [
                'id' => $emoji->id,
                'name' => $emoji->name
            ];
            if (isset($emoji->animated)) {
                $partial['animated'] = $emoji->animated;
            }
            $emoji = $partial;
        } elseif (is_array($emoji)) {
            if (!isset($emoji['id'], $emoji['name'])) {
                throw new \InvalidArgumentException('Emoji must have an id and name field.');
            }
            $partial = [
                'id' => $emoji['id'],
                'name' => $emoji['name'],
            ];
            if (isset($emoji['animated'])) {
                $partial['animated'] = $emoji['animated'];
            }
            $emoji = $partial;
        }

        $this->emoji = $emoji;
    }

    public function jsonSerialize(): array
    {
        $data = [
            'label' => $this->label,
            'value' => $this->value,
        ];

        if (isset($this->description)) {
            $data['description'] = $this->description;
        }
        if (isset($this->emoji)) {
            $data['emoji'] = $this->emoji;
        }
        if (isset($this->default)) {
            $data['default'] = $this->default;
        }

        return $data;
    }
}
