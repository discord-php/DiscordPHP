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

namespace Discord\Parts\Channel\Message;

/**
 * A Checkbox is a single interactive component for simple yes/no style questions. Checkboxes are available in modals and must be placed inside a Labl.
 *
 * @link https://discord.com/developers/docs/components/reference#checkbox
 *
 * @since 10.46.0
 *
 * @property int    $type      23 for a checkbox.
 * @property int    $id        Unique identifier for component.
 * @property string $custom_id Developer-defined identifier for the input; 1-100 characters.
 * @property bool   $value     Whether the checkbox is checked or not.
 */
class Checkbox extends Interactive
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'type',
        'id',
        'custom_id',
        'value',
    ];
}
