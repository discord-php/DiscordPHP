<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Embed;

use Discord\Parts\Part;

/**
 * A field of an embed object.
 *
 * @property string $name   The name of the field.
 * @property string $value  The value of the field.
 * @property bool   $inline Whether the field should be displayed in-line.
 */
class Field extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['name', 'value', 'inline'];

    /**
     * Gets the inline attribute.
     *
     * @return bool The inline attribute.
     */
    public function getInlineAttribute()
    {
        if (! array_key_exists('inline', $this->attributes)) {
            return false;
        }

        return $this->attributes['inline'];
    }
}
