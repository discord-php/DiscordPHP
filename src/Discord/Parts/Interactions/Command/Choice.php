<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Parts\Part;

/**
 * Choice represents a choice that can be given to a command.
 *
 * @author David Cole <david.cole1340@gmail.com>
 * 
 * @property string $name       1-100 character choice name.
 * @property string|int|float   $value  Value of the choice, up to 100 characters if string.
 */
class Choice extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['name', 'value'];
}
