<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * A field of an embed object.
 *
 * @link https://discord.com/developers/docs/resources/channel#embed-object-embed-field-structure
 *
 * @since 4.0.3
 *
 * @property string    $name   The name of the field.
 * @property string    $value  The value of the field.
 * @property bool|null $inline Whether the field should be displayed in-line.
 */
class Field extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'name',
        'value',
        'inline',
    ];

    /**
     * Gets the inline attribute.
     *
     * @return bool The inline attribute.
     */
    protected function getInlineAttribute(): bool
    {
        return (bool) ($this->attributes['inline'] ?? false);
    }
}
