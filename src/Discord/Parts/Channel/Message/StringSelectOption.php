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

use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Discord\Repository\Guild\EmojiRepository;

/**
 * Specified choices in a string select menu; max 25
 *
 * @link https://discord.com/developers/docs/components/reference#string-select-select-option-structure
 *
 * @since 10.11.0
 *
 * @property string      $label       User-facing name of the option; max 100 characters.
 * @property string      $value       Dev-defined value of the option; max 100 characters.
 * @property string|null $description Additional description of the option; max 100 characters.
 * @property Emoji|null  $emoji       Partial emoji object: id, name, and animated
 * @property bool|null   $default     Will show this option as selected by default.
 */
class StringSelectOption extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'label',
        'value',
        'description',
        'emoji',
        'default',
    ];

    protected function getEmojiAttribute(): Emoji
    {
        return $this->createOf(Emoji::class, $this->attributes['emoji'], true);
    }
}
