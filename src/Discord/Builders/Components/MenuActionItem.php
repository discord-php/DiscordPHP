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

/**
 * Builds one action inside an overflow menu group.
 *
 * Link actions require a URL. Default and Danger actions must omit the URL.
 * The optional numeric component ID is not a custom ID for registering callbacks.
 *
 * @property-read int            $type        Component type 26.
 * @property int|string|null            $id          Optional numeric component ID.
 * @property string|null         $label       User-facing action text; required on serialization.
 * @property int                 $style       One of the STYLE_* constants.
 * @property string|null         $description Optional user-facing description.
 * @property string|null         $url         Destination for link actions only.
 * @property array{id?: string|null, name?: string|null, animated?: bool}|Emoji|string|null $emoji The optional emoji in its original input form.
 *
 * @property string|null $custom_id Developer-defined ID for Default or Danger actions.
 * @property bool|null $disabled Whether the action is disabled; null omits the field.
 *
 * @see MenuActionItemPart For the received message component.
 */
class MenuActionItem extends Interactive
{
    use OverflowMenuTextLimits;

    /** An ordinary menu action. */
    public const STYLE_DEFAULT = 1;
    /** A potentially destructive menu action. */
    public const STYLE_DANGER = 2;
    /** Opens a URL rather than running an action callback. */
    public const STYLE_LINK = 3;

    protected $type = ComponentObject::TYPE_MENU_ACTION_ITEM;

    protected ?string $label = null;
    protected int $style = self::STYLE_DEFAULT;
    protected ?string $description = null;
    protected ?string $url = null;
    protected ?bool $disabled = null;
    protected array|Emoji|string|null $emoji = null;

    /**
     * Creates an action with a label and style.
     *
     * @param string $label Non-empty action text (maximum 80 characters).
     * @param int    $style One of the STYLE_* constants.
     * @throws \InvalidArgumentException If the label is empty or the style is unknown.
     * @throws \LengthException If the label exceeds 80 characters.
     * @return self
     */
    public static function new(string $label, int $style = self::STYLE_DEFAULT): self
    {
        return (new self())->setLabel($label)->setStyle($style);
    }

    /**
     * Sets Discord's optional numeric component ID, not an interaction custom ID.
     *
     * @param int|string|null $id An integer, an integer string, or null to clear.
     * @throws \InvalidArgumentException If a string does not represent an integer.
     * @return self
     */
    protected function setId(int|string|null $id = null): self
    {
        if (is_string($id) && filter_var($id, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException('A component ID must be an integer.');
        }

        $this->id = $id === null ? null : (int) $id;

        return $this;
    }

    /** @return int|null The numeric component ID, independent of custom_id. */
    protected function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Sets the action identifier returned in Default and Danger interactions.
     * @param string|null $custom_id One to 100 characters, or null to clear.
     * @throws \InvalidArgumentException If the identifier is empty.
     * @throws \LengthException If the identifier exceeds 100 characters.
     * @return self
     */
    public function setCustomId(?string $custom_id): self
    {
        if ($custom_id === '') {
            throw new \InvalidArgumentException('A menu action item custom ID cannot be empty.');
        }

        if ($custom_id !== null) {
            self::validateOverflowTextLength($custom_id, 100, 'A menu action item custom ID');
        }

        $this->custom_id = $custom_id;

        return $this;
    }

    /**
     * Sets whether the item is disabled.
     * @param bool|null $disabled Null omits the field and uses Discord's default.
     * @return self
     */
    public function setDisabled(?bool $disabled = true): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /** @return bool|null The configured disabled value, if any. */
    protected function getDisabled(): ?bool
    {
        return $this->disabled;
    }

    /**
     * Rebuilds an editable action from a received message part.
     *
     * @param Part $part A received MenuActionItemPart instance.
     * @throws \InvalidArgumentException If the part is not a MenuActionItemPart.
     * @return self
     */
    public static function fromPart(Part $part): self
    {
        if (! $part instanceof MenuActionItemPart) {
            throw new \InvalidArgumentException('Expected a menu action item part.');
        }

        $item = self::new($part->label, $part->style)
            ->setCustomId($part->custom_id)
            ->setDisabled($part->disabled)
            ->setDescription($part->description)
            ->setUrl($part->url)
            ->setEmoji($part->emoji);
        $item->setId($part->id);

        return $item;
    }

    /**
     * Sets the visible action text.
     *
     * @param string $label Non-empty action text (maximum 80 characters).
     * @throws \InvalidArgumentException If the label is empty.
     * @throws \LengthException If the label exceeds 80 characters.
     * @return self
     */
    public function setLabel(string $label): self
    {
        if ($label === '') {
            throw new \InvalidArgumentException('A menu action item label cannot be empty.');
        }

        self::validateOverflowTextLength($label, 80, 'A menu action item label');

        $this->label = $label;

        return $this;
    }

    /**
     * Returns the label, or null before a label has been set.
     *
     * @return string|null
     */
    protected function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Sets the action style.
     *
     * @param int $style One of the STYLE_* constants.
     * @throws \InvalidArgumentException If the style is unknown.
     * @return self
     */
    public function setStyle(int $style): self
    {
        if (! in_array($style, [self::STYLE_DEFAULT, self::STYLE_DANGER, self::STYLE_LINK], true)) {
            throw new \InvalidArgumentException('Unknown menu action item style.');
        }

        $this->style = $style;

        return $this;
    }

    /**
     * Returns the current style.
     *
     * @return int
     */
    protected function getStyle(): int
    {
        return $this->style;
    }

    /**
     * Sets the optional text shown with the action.
     *
     * @param string|null $description Text (maximum 100 characters), or null to omit.
     * @throws \LengthException If the description exceeds 100 characters.
     * @return self
     */
    public function setDescription(?string $description): self
    {
        if ($description !== null) {
            self::validateOverflowTextLength($description, 100, 'A menu action item description');
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Returns the description, or null if omitted.
     *
     * @return string|null
     */
    protected function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the link destination.
     *
     * @param string|null $url Non-empty URL for a link action, or null to omit.
     * @throws \InvalidArgumentException If the URL is an empty string.
     * @return self
     */
    public function setUrl(?string $url): self
    {
        if ($url === '') {
            throw new \InvalidArgumentException('A menu action item URL cannot be empty.');
        }

        $this->url = $url;

        return $this;
    }

    /**
     * Returns the URL, or null if omitted.
     *
     * @return string|null
     */
    protected function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Sets or clears the optional action emoji.
     *
     * @param array{id?: string|null, name?: string|null, animated?: bool}|Emoji|string|null $emoji Partial emoji data, an Emoji part, a Unicode emoji, or null.
     * @return self
     */
    public function setEmoji(array|Emoji|string|null $emoji): self
    {
        $this->emoji = $emoji;

        return $this;
    }

    /**
     * Returns the emoji in the form passed to setEmoji(), or null if omitted.
     *
     * @return array{id?: string|null, name?: string|null, animated?: bool}|Emoji|string|null
     */
    protected function getEmoji(): array|Emoji|string|null
    {
        return $this->emoji;
    }

    /**
     * Builds the API payload for this action and omits unset optional fields.
     *
     * @throws \DomainException If the label is missing or required or disallowed fields do not match the selected style.
     * @return array{type: int, label: string, style: int, id?: int, custom_id?: string, disabled?: bool, description?: string, url?: string, emoji?: array{id?: string|null, name?: string|null, animated?: bool}}
     */
    public function jsonSerialize(): array
    {
        if ($this->label === null) {
            throw new \DomainException('A menu action item requires a label.');
        }

        if (($this->style === self::STYLE_LINK) !== ($this->url !== null)) {
            throw new \DomainException('A URL is required for link menu actions and must be omitted for other styles.');
        }

        if (($this->style === self::STYLE_LINK) === ($this->custom_id !== null)) {
            throw new \DomainException('A custom ID is required for Default and Danger actions and must be omitted for Link actions.');
        }

        $data = [
            'type' => $this->type,
            'label' => $this->label,
            'style' => $this->style,
        ];

        foreach (['id', 'custom_id', 'description', 'url', 'disabled'] as $key) {
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
