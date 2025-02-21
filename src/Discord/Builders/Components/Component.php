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

use JsonSerializable;

/**
 * Components are a new field on the message object, so you can use them whether
 * you're sending messages or responding to a slash command or other interaction.
 *
 * @link https://discord.com/developers/docs/interactions/message-components#what-is-a-component
 *
 * @since 7.0.0
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
    public const TYPE_SECTION = 9;
    public const TYPE_TEXT_DISPLAY = 10;
    public const TYPE_THUMBNAIL = 11;
    public const TYPE_MEDIA_GALLERY = 12;
    public const TYPE_FILE = 13;
    public const TYPE_SEPARATOR = 14;
    public const TYPE_CONTENT_INVENTORY_ENTRY = 16;
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
        return uniqid(time(), true);
    }
}
