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

use Discord\Builders\Builder;
use JsonSerializable;

/**
 * Components are a new field on the message object, so you can use them whether
 * you're sending messages or responding to a slash command or other interaction.
 *
 * @link https://discord.com/developers/docs/components/reference#what-is-a-component
 *
 * @since 7.0.0
 * @deprecated 10.9.0 Use `ComponentObject` instead.
 */
abstract class Component extends Builder implements JsonSerializable
{
    /** Container to display a row of interactive components. */
    public const TYPE_ACTION_ROW = ComponentObject::TYPE_ACTION_ROW;
    /** Button object. */
    public const TYPE_BUTTON = ComponentObject::TYPE_BUTTON;
    /** Select menu for picking from defined text options. */
    public const TYPE_STRING_SELECT = ComponentObject::TYPE_STRING_SELECT;
    /** Text input object. */
    public const TYPE_TEXT_INPUT = ComponentObject::TYPE_TEXT_INPUT;
    /** Select menu for users. */
    public const TYPE_USER_SELECT = ComponentObject::TYPE_USER_SELECT;
    /** Select menu for roles. */
    public const TYPE_ROLE_SELECT = ComponentObject::TYPE_ROLE_SELECT;
    /** Select menu for mentionables (users and roles). */
    public const TYPE_MENTIONABLE_SELECT = ComponentObject::TYPE_MENTIONABLE_SELECT;
    /** Select menu for channels. */
    public const TYPE_CHANNEL_SELECT = ComponentObject::TYPE_CHANNEL_SELECT;

    // Requires IS_COMPONENTS_V2 flag
    /** Container to display text alongside an accessory component. */
    public const TYPE_SECTION = ComponentObject::TYPE_SECTION;
    /** Markdown text. */
    public const TYPE_TEXT_DISPLAY = ComponentObject::TYPE_TEXT_DISPLAY;
    /** Small image that can be used as an accessory. */
    public const TYPE_THUMBNAIL = ComponentObject::TYPE_THUMBNAIL;
    /** Display images and other media. */
    public const TYPE_MEDIA_GALLERY = ComponentObject::TYPE_MEDIA_GALLERY;
    /** Displays an attached file. */
    public const TYPE_FILE = ComponentObject::TYPE_FILE;
    /** Component to add vertical padding between other components. */
    public const TYPE_SEPARATOR = ComponentObject::TYPE_SEPARATOR;
    /** Undocumented. */
    public const TYPE_CONTENT_INVENTORY_ENTRY = ComponentObject::TYPE_CONTENT_INVENTORY_ENTRY;
    /** Container that visually groups a set of components. */
    public const TYPE_CONTAINER = ComponentObject::TYPE_CONTAINER;

    /** Container associating a label and description with a component. */
    public const TYPE_LABEL = ComponentObject::TYPE_LABEL;
    /** Component for uploading files. */
    public const TYPE_FILE_UPLOAD = ComponentObject::TYPE_FILE_UPLOAD;
    /** Undocumented. Used by the client for checkpoint. */
    public const TYPE_CHECKPOINT_CARD = ComponentObject::TYPE_CHECKPOINT_CARD;

    /** @deprecated 7.4.0 Use `Component::TYPE_STRING_SELECT` */
    public const TYPE_SELECT_MENU = ComponentObject::TYPE_STRING_SELECT;

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
