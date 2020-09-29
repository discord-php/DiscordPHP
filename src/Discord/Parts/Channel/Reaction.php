<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/**
 * Defines the reaction object that a message contains.
 *
 * @property int $count Number of reactions.
 * @property bool $me Whether the current bot has reacted.
 * @property Emoji $emoji The emoji that was reacted with.
 */
class Reaction extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['count', 'me', 'emoji'];

    /**
     * Gets the partial emoji attribute.
     *
     * @return Emoji
     * @throws \Exception
     */
    protected function getEmojiAttribute(): ?Part
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }

        return null;
    }
}
