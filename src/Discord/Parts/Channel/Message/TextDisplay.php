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

/**
 * A Text Display is a top-level content component that allows you to add text to your message formatted with markdown and mention users and roles. This is similar to the content field of a message, but allows you to add multiple text components, controlling the layout of your message.
 *
 * Text Displays are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#text-display
 *
 * @since 10.11.0
 *
 * @property int         $type    10 for text display.
 * @property string|null $id      Optional identifier for component.
 * @property string      $content Text that will be displayed similar to a message.
 */
class TextDisplay extends Content
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'content',
    ];
}
