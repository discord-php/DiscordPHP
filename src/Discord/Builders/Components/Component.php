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
 * Components are a new field on the message object, so you can use them whether you're sending messages or responding to a slash command or other interaction.
 *
 * @see https://discord.com/developers/docs/interactions/message-components#what-is-a-component
 */
abstract class Component implements JsonSerializable
{
    public const TYPE_ACTION_ROW = 1;
    public const TYPE_BUTTON = 2;
    public const TYPE_SELECT_MENU = 3;
    public const TYPE_TEXT_INPUT = 4;

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
