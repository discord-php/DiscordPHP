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
 * A Container is a top-level layout component. Containers are visually distinct from surrounding components and have an optional customizable color bar.
 *
 * Containers are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#container
 *
 * @since 10.11.0
 *
 * @property int                               $type         17 for container component.
 * @property string|null                       $id           Optional identifier for component.
 * @property ExCollectionInterface|Component[] $components   Components of the type action row, text display, section, media gallery, separator, or file.
 * @property int|null                          $accent_color Color for the accent on the container as RGB from 0x000000 to 0xFFFFFF.
 * @property bool|null                         $spoiler      Whether the container should be a spoiler (or blurred out). Defaults to false.
 */
class Container extends Layout
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'components',
        'accent_color',
        'spoiler',
    ];
}
