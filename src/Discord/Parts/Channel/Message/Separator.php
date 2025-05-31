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
 * A Separator is a top-level layout component that adds vertical padding and visual division between other components.
 *
 * Separators are only available in messages.
 *
 * @link https://discord.com/developers/docs/components/reference#user-select
 *
 * @since 10.11.0
 *
 * @property int          $type    14 for separator component.
 * @property string|null  $id      Optional identifier for component.
 * @property bool|null    $divider Whether a visual divider should be displayed in the component. Defaults to true.
 * @property integer|null $spacing Size of separator padding—1 for small padding, 2 for large padding. Defaults to 1
 */
class Separator extends Layout
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'id',
        'divider',
        'spacing',
    ];
}
