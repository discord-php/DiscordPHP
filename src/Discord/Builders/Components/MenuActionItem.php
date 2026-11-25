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

use Discord\Parts\Channel\Message\MenuActionItem as MenuActionItemPart;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/** A single action within an overflow menu group. */
class MenuActionItem extends ComponentObject
{
    public const STYLE_DEFAULT = 1;
    public const STYLE_DANGER = 2;
    public const STYLE_LINK = 3;

    protected $type = self::TYPE_MENU_ACTION_ITEM;

    private ?string $label = null;
    private int $style = self::STYLE_DEFAULT;
    private ?string $description = null;
    private ?string $url = null;
    private array|Emoji|string|null $emoji = null;

    public static function new(string $label, int $style = self::STYLE_DEFAULT): self
    {
        return (new self())->setLabel($label)->setStyle($style);
    }

    /** Set Discord's optional numeric component ID. This is not a custom ID. */
    public function setId(int|string|null $id = null): self
    {
        if (is_string($id) && filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('A component ID must be an integer.');
        }

        $this->id = $id === null ? null : (int) $id;

        return $this;
    }

    public static function fromPart(Part $part): self
    {
        if (! $part instanceof MenuActionItemPart) {
            throw new \InvalidArgumentException('Expected a menu action item part.');
        }

        $item = self::new($part->label, $part->style)
            ->setDescription($part->description)
            ->setUrl($part->url)
            ->setEmoji($part->emoji);
        $item->id = $part->id;

        return $item;
    }

    public function setLabel(string $label): self
    {
        if ($label === '') {
            throw new \InvalidArgumentException('A menu action item label cannot be empty.');
        }

        $this->label = $label;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setStyle(int $style): self
    {
        if (! in_array($style, [self::STYLE_DEFAULT, self::STYLE_DANGER, self::STYLE_LINK], true)) {
            throw new \InvalidArgumentException('Unknown menu action item style.');
        }

        $this->style = $style;

        return $this;
    }

    public function getStyle(): int
    {
        return $this->style;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setUrl(?string $url): self
    {
        if ($url === '') {
            throw new \InvalidArgumentException('A menu action item URL cannot be empty.');
        }

        $this->url = $url;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param array{id?: string|null, name?: string|null, animated?: bool}|Emoji|string|null $emoji Partial emoji, custom emoji part, Unicode emoji, or null.
     */
    public function setEmoji(array|Emoji|string|null $emoji): self
    {
        $this->emoji = $emoji;

        return $this;
    }

    public function getEmoji(): array|Emoji|string|null
    {
        return $this->emoji;
    }

    public function jsonSerialize(): array
    {
        if ($this->label === null) {
            throw new \DomainException('A menu action item requires a label.');
        }

        if (($this->style === self::STYLE_LINK) !== ($this->url !== null)) {
            throw new \DomainException('A URL is required for link menu actions and must be omitted for other styles.');
        }

        $data = [
            'type' => $this->type,
            'label' => $this->label,
            'style' => $this->style,
        ];

        foreach (['id', 'description', 'url'] as $key) {
            if (isset($this->{$key})) {
                $data[$key] = $key === 'id' ? (int) $this->id : $this->{$key};
            }
        }

        if (isset($this->emoji)) {
            $data['emoji'] = match (true) {
                $this->emoji instanceof Emoji => array_intersect_key($this->emoji->getRawAttributes(), array_flip(['id', 'name', 'animated'])),
                is_string($this->emoji) => ['name' => $this->emoji],
                default => $this->emoji,
            };
        }

        return $data;
    }
}
