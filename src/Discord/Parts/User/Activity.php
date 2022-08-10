<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;

/**
 * The Activity part describes activities the member is undertaking.
 *
 * @see https://discord.com/developers/docs/topics/gateway#activity-object
 *
 * @property string        $name
 * @property int           $type
 * @property ?string|null  $url
 * @property Carbon|null   $created_at
 * @property object|null   $timestamps
 * @property string|null   $application_id
 * @property ?string|null  $details
 * @property ?string|null  $state
 * @property Emoji|null    $emoji
 * @property object|null   $party
 * @property object|null   $assets
 * @property object|null   $secrets
 * @property bool|null     $instance
 * @property int|null      $flags
 * @property object[]|null $buttons
 */
class Activity extends Part
{
    public const TYPE_PLAYING = 0; // Playing {$this->name}
    public const TYPE_STREAMING = 1; // Streaming {$this->details}
    public const TYPE_LISTENING = 2; // Listening to {$this->name}
    public const TYPE_WATCHING = 3; // Watching {$this->name}
    public const TYPE_CUSTOM = 4; // {$this->emoji} {$this->name}
    public const TYPE_COMPETING = 5; // Competing in {$this->name}

    public const FLAG_INSTANCE = (1 << 0);
    public const FLAG_JOIN = (1 << 1);
    public const FLAG_SPECTATE = (1 << 2);
    public const FLAG_JOIN_REQUEST = (1 << 3);
    public const FLAG_SYNC = (1 << 4);
    public const FLAG_PLAY = (1 << 5);
    public const FLAG_PARTY_PRIVACY_FRIENDS = (1 << 6);
    public const FLAG_PARTY_PRIVACY_VOICE_CHANNEL = (1 << 7);
    public const FLAG_EMBEDDED = (1 << 8);

    public const STATUS_ONLINE = 'online';
    public const STATUS_IDLE = 'idle';
    public const STATUS_DND = 'dnd';
    public const STATUS_INVISIBLE = 'invisible';

    /**
     * @inheritdoc
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
        'buttons',
    ];

    /**
     * Gets the created at timestamp.
     *
     * @return Carbon|null
     */
    protected function getCreatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['created_at'])) {
            return null;
        }

        return Carbon::createFromTimestamp($this->attributes['created_at']);;
    }

    /**
     * Gets the emoji object of the activity.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if (! isset($this->attributes['emoji'])) {
            return null;
        }

        return $this->factory->part(Emoji::class, (array) $this->attributes['emoji'], true);
    }

    /**
     * Converts the activity to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        switch ($this->type) {
            case self::TYPE_PLAYING:
                return 'Playing '.$this->name;
            case self::TYPE_STREAMING:
                return 'Streaming '.$this->details;
            case self::TYPE_LISTENING:
                return 'Listening to '.$this->name;
            case self::TYPE_WATCHING:
                return 'Watching '.$this->name;
            case self::TYPE_CUSTOM:
                return "{$this->emoji} {$this->name}";
            case self::TYPE_COMPETING:
                return 'Competing in '.$this->name;
        }

        return $this->name;
    }
}
