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

namespace Discord\Builders;

use Discord\Builders\Components\Container;
use Discord\Builders\ComponentEmbed\ComponentValidator;
use Discord\Parts\Part;
use JsonSerializable;

/**
 * Authors display-only website link previews, without a Discord client or bot.
 *
 * Put toScript() in the server-rendered HTML head alongside Open Graph fallback tags.
 * This is not a MessageBuilder payload and cannot send interactions.
 *
 * @link https://docs.discord.com/developers/link-previews/component-embeds
 *
 * @property array|null $component Validated website root Container snapshot.
 */
class ComponentEmbedBuilder extends Builder implements JsonSerializable
{
    /** @var array|null Validated snapshot of the root Container. */
    protected ?array $component = null;

    /** Creates a website preview from a Container builder or an API-shaped array. */
    public static function new(Container|array $component): self
    {
        return (new self())->setComponent($component);
    }

    /**
     * Received previews include fetched media metadata and are not website authoring payloads.
     *
     * @throws \BadMethodCallException
     */
    public static function fromPart(Part $part): self
    {
        throw new \BadMethodCallException('Author a website preview with a Container builder or array instead of received '.get_class($part).'.');
    }

    /**
     * Snapshots and validates the tree, including the final script-safe JSON byte length.
     *
     * @throws \InvalidArgumentException Invalid preview component or media.
     * @throws \LengthException          More than 3,000 UTF-8 bytes, 40 components or 10 gallery items.
     * @throws \JsonException            Invalid UTF-8 or a value that cannot be encoded.
     */
    public function setComponent(Container|array $component): self
    {
        // Resolve nested builders once, so later mutations cannot bypass validation.
        $component = json_decode(json_encode($component, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        ComponentValidator::validate($component);
        self::encode($component);
        $this->component = $component;

        return $this;
    }

    /** Returns the validated root Container snapshot. */
    public function getComponent(): ?array
    {
        return $this->component;
    }

    /** @inheritDoc */
    public function jsonSerialize(): array
    {
        if ($this->component === null) {
            throw new \LogicException('A website component embed requires a root Container.');
        }

        return ['component' => $this->component];
    }

    /** Returns JSON for inline scripts or a linked JSON document. Escapes count toward 3,000 bytes. */
    public function toJson(): string
    {
        return self::encode($this->jsonSerialize()['component']);
    }

    /** Returns the complete script element for a server-rendered HTML head. */
    public function toScript(): string
    {
        return '<script id="discord:component-embed" type="application/json">'.$this->toJson().'</script>';
    }

    private static function encode(array $component): string
    {
        $json = json_encode(['component' => $component], JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (strlen($json) > 3000) {
            throw new \LengthException('Website component embed JSON cannot exceed 3,000 UTF-8 bytes, including escapes.');
        }

        return $json;
    }
}
