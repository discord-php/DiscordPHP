<?php

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
     */
    public function getEmojiAttribute()
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }
    }
}