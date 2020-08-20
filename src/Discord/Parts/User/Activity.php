<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/**
 * The Activity part describes activities the member is undertaking.
 *
 * @property string $name
 * @property int $type
 * @property string $url
 * @property \Carbon\Carbon $created_at
 * @property array $timestamps
 * @property string $application_id
 * @property string $details
 * @property string $state
 * @property \Discord\Parts\Guild\Emoji $emoji
 * @property object $party
 * @property object $assets
 * @property object $secrets
 * @property bool $instance
 * @property int $flags
 */
class Activity extends Part
{
    const TYPE_PLAYING = 0; // Playing {$this->name}
    const TYPE_STREAMING = 1; // Streaming {$this->details}
    const TYPE_LISTENING = 2; // Listening to {$this->name}
    const TYPE_CUSTOM = 4; // {$this->emoji} {$this->name}

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'name',
        'url',
        'type',
        'created_at',
        'timestamps',
        'application_id',
        'details',
        'state',
        'emoji',
        'party',
        'assets',
        'secrets',
        'instance',
        'flags',
    ];

    /**
     * Gets the created at timestamp.
     *
     * @return \Carbon\Carbon
     */
    public function getCreatedAtAttribute()
    {
        if (isset($this->attributes['created_at'])) {
            return Carbon::createFromTimestamp($this->attributes['created_at']);
        }
    }

    /**
     * Gets the emoji object of the activity.
     *
     * @return \Discord\Parts\Guild\Emoji
     */
    public function getEmojiAttribute()
    {
        if (isset($this->attributes['emoji'])) {
            return $this->factory->create(Emoji::class, $this->attributes['emoji'], true);
        }
    }

    /**
     * Converts the activity to a string.
     *
     * @return string
     */
    public function __toString()
    {
        switch ($this->type) {
            case self::TYPE_PLAYING:
                return "Playing {$this->name}";
                break;
            case self::TYPE_STREAMING:
                return "Streaming {$this->details}";
                break;
            case self::TYPE_LISTENING:
                return "Listening to {$this->name}";
                break;
            case self::TYPE_CUSTOM:
                return "{$this->emoji} {$this->name}";
                break;
        }
    }
}
