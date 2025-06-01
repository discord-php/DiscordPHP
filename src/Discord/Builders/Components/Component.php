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

use JsonSerializable;

/**
 * Components are a new field on the message object, so you can use them whether
 * you're sending messages or responding to a slash command or other interaction.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#what-is-a-component
 *
 * @since 7.0.0
 * @deprecated 10.9.0 Use `ComponentObject` instead.
 */
abstract class Component implements JsonSerializable
{
    public const TYPE_ACTION_ROW = 1;
    public const TYPE_BUTTON = 2;
    public const TYPE_STRING_SELECT = 3;
    public const TYPE_TEXT_INPUT = 4;
    public const TYPE_USER_SELECT = 5;
    public const TYPE_ROLE_SELECT = 6;
    public const TYPE_MENTIONABLE_SELECT = 7;
    public const TYPE_CHANNEL_SELECT = 8;
    // Requires IS_COMPONENTS_V2 flag
    public const TYPE_SECTION = 9;
    public const TYPE_TEXT_DISPLAY = 10;
    public const TYPE_THUMBNAIL = 11;
    public const TYPE_MEDIA_GALLERY = 12;
    public const TYPE_FILE = 13;
    public const TYPE_SEPARATOR = 14;
    public const TYPE_CONTENT_INVENTORY_ENTRY = 16; // Not documented
    public const TYPE_CONTAINER = 17;

    /** @deprecated 7.4.0 Use `Component::TYPE_STRING_SELECT` */
    public const TYPE_SELECT_MENU = 3;

    /**
     * Generates a UUID which can be used for component custom IDs.
     *
     * @return string
     */
    protected static function generateUuid(): string
    {
        return uniqid((string) time(), true);
    }

    /**
     * Retrieves the type of the component.
     * Only ComponentObjects will have this property set.
     *
     * @return int|null
     *
     * @see Discord\Builders\Components\ComponentObject
     */
    protected function getType(): ?int
    {
        return $this->type ?? null;
    }

    /**
     * The id field is optional and is used to identify components in the response from an interaction that aren't interactive components.
     * The id must be unique within the message and is generated sequentially if left empty.
     * Generation of ids won't use another id that exists in the message if you have one defined for another component.
     *
     * @return int|null
     */
    protected function getId()
    {
        return $this->id ?? null;
    }

    /**
     * Returns the custom ID of the button.
     *
     * @return string|null
     */
    public function getCustomId(): ?string
    {
        return $this->custom_id ?? null;
    }
}
