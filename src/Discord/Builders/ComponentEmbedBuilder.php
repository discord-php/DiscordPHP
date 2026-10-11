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
use Discord\Parts\Part;
use JsonSerializable;

use function Discord\poly_strlen;

/**
 * Authors display-only website link previews, without a Discord client or bot.
 *
 * Put toScript() in the server-rendered HTML head alongside Open Graph fallback tags.
 * This is not a MessageBuilder payload and cannot send interactions.
 *
 * @link https://docs.discord.com/developers/link-previews/component-embeds
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
        throw new \BadMethodCallException('Author a website preview with a Container builder or array instead of a received Part.');
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
        $count = $galleryItems = 0;
        self::validateComponent($component, [17], $count, $galleryItems);
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

    private static function validateComponent(array $component, array $allowed, int &$count, int &$galleryItems): void
    {
        $type = $component['type'] ?? null;
        if (! in_array($type, $allowed, true)) {
            throw new \InvalidArgumentException('Unsupported component type or placement in website preview.');
        }
        if (++$count > 40) {
            throw new \LengthException('Website component embeds allow at most 40 components including the root Container.');
        }
        $fields = [
            1 => ['components'],
            2 => ['style', 'url', 'label', 'emoji', 'disabled'],
            9 => ['components', 'accessory'],
            10 => ['content'],
            11 => ['media', 'description', 'spoiler'],
            12 => ['items'],
            14 => ['divider', 'spacing'],
            17 => ['components', 'accent_color', 'spoiler'],
        ];
        self::keys($component, array_merge(['type', 'id'], $fields[$type]));
        if (isset($component['id']) && (! is_int($component['id']) || $component['id'] < 0 || $component['id'] > 4294967295)) {
            throw new \InvalidArgumentException('Component id must be an unsigned 32-bit integer.');
        }
        foreach (['spoiler', 'disabled', 'divider'] as $field) {
            if (array_key_exists($field, $component) && ! is_bool($component[$field])) {
                throw new \InvalidArgumentException($field.' must be a boolean.');
            }
        }
        if ($type === 17 && array_key_exists('accent_color', $component) && $component['accent_color'] !== null
            && (! is_int($component['accent_color']) || $component['accent_color'] < 0 || $component['accent_color'] > 0xFFFFFF)) {
            throw new \InvalidArgumentException('Container accent_color must be an RGB integer or null.');
        }
        if ($type === 14 && isset($component['spacing']) && ! in_array($component['spacing'], [1, 2], true)) {
            throw new \InvalidArgumentException('Separator spacing must be 1 or 2.');
        }
        if ($type === 10) {
            self::text($component['content'] ?? null, 4000);
        }
        if ($type === 2) {
            if (($component['style'] ?? null) !== 5 || (! isset($component['label']) && ! isset($component['emoji']))) {
                throw new \InvalidArgumentException('Website buttons require link style 5 and a label or emoji.');
            }
            self::url($component['url'] ?? null);
            if (isset($component['label'])) {
                self::text($component['label'], 80);
            }
            if (isset($component['emoji'])) {
                if (! is_array($component['emoji'])) {
                    throw new \InvalidArgumentException('Button emoji must be an object.');
                }
                self::keys($component['emoji'], ['id', 'name', 'animated']);
                if (empty($component['emoji']['id']) && empty($component['emoji']['name'])) {
                    throw new \InvalidArgumentException('Button emoji requires an id or name.');
                }
            }
        }
        if ($type === 11) {
            self::media($component['media'] ?? null);
            if (isset($component['description'])) {
                self::text($component['description'], 1024);
            }
        }
        if ($type === 12) {
            self::items($component['items'] ?? null, 10);
            $galleryItems += count($component['items']);
            if ($galleryItems > 10) {
                throw new \LengthException('Website previews allow at most 10 gallery items across all galleries.');
            }
            foreach ($component['items'] as $item) {
                if (! is_array($item)) {
                    throw new \InvalidArgumentException('Gallery items must be objects.');
                }
                self::keys($item, ['media', 'description', 'spoiler']);
                self::media($item['media'] ?? null);
                if (isset($item['description'])) {
                    self::text($item['description'], 1024);
                }
                if (array_key_exists('spoiler', $item) && ! is_bool($item['spoiler'])) {
                    throw new \InvalidArgumentException('Gallery spoiler must be a boolean.');
                }
            }
        }
        if (in_array($type, [1, 9, 17], true)) {
            self::items($component['components'] ?? null, $type === 1 ? 5 : ($type === 9 ? 3 : 39));
            $children = [1 => [2], 9 => [10], 17 => [1, 9, 10, 12, 14]];
            foreach ($component['components'] as $child) {
                if (! is_array($child)) {
                    throw new \InvalidArgumentException('Components must be objects.');
                }
                self::validateComponent($child, $children[$type], $count, $galleryItems);
            }
        }
        if ($type === 9) {
            if (! is_array($component['accessory'] ?? null)) {
                throw new \InvalidArgumentException('Section requires a Button or Thumbnail accessory.');
            }
            self::validateComponent($component['accessory'], [2, 11], $count, $galleryItems);
        }
    }

    private static function keys(array $object, array $allowed): void
    {
        if (array_diff(array_keys($object), $allowed)) {
            throw new \InvalidArgumentException('Unexpected field in website component embed.');
        }
    }

    private static function items($items, int $max): void
    {
        if (! is_array($items) || ! array_is_list($items) || count($items) < 1 || count($items) > $max) {
            throw new \InvalidArgumentException('Invalid component or media item list.');
        }
    }

    private static function text($text, int $max): void
    {
        if (! is_string($text) || poly_strlen($text) < 1 || poly_strlen($text) > $max) {
            throw new \InvalidArgumentException('Invalid website component text length.');
        }
    }

    private static function url($url): void
    {
        if (! is_string($url) || poly_strlen($url) > 2048 || ! filter_var($url, FILTER_VALIDATE_URL)
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('Website preview URLs must be HTTP(S) and at most 2,048 characters.');
        }
    }

    private static function media($media): void
    {
        if (! is_array($media)) {
            throw new \InvalidArgumentException('Media must be an object with a URL.');
        }
        self::keys($media, ['url']);
        self::url($media['url'] ?? null);
        if (strtolower(pathinfo((string) parse_url($media['url'], PHP_URL_PATH), PATHINFO_EXTENSION)) === 'svg') {
            throw new \InvalidArgumentException('SVG preview media is unsupported; use a supported raster image.');
        }
    }
}
