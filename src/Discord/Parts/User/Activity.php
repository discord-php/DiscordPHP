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
 * @link https://discord.com/developers/docs/topics/gateway-events#activity-object
 *
 * @since 5.0.0 Renamed from Game to Activity
 * @since 3.2.2
 *
 * @property string        $name           The activity's name.
 * @property int           $type           Activity type.
 * @property ?string|null  $url            Stream url, is validated when type is 1.
 * @property Carbon|null   $created_at     Timestamp of when the activity was added to the user's session.
 * @property object|null   $timestamps     Unix timestamps for start and/or end of the game.
 * @property string|null   $application_id Application id for the game.
 * @property ?string|null  $details        What the player is currently doing.
 * @property ?string|null  $state          The user's current party status, or text used for a custom status.
 * @property Emoji|null    $emoji          The emoji used for a custom status.
 * @property object|null   $party          Information for the current party of the player.
 * @property object|null   $assets         Images for the presence and their hover texts.
 * @property object|null   $secrets        Secrets for Rich Presence joining and spectating.
 * @property bool|null     $instance       Whether or not the activity is an instanced game session.
 * @property int|null      $flags          Activity flags `OR`d together, describes what the payload includes.
 * @property object[]|null $buttons        The custom buttons shown in the Rich Presence (max 2).
 */
class Activity extends Part
{
    /** Playing {name} */
    public const TYPE_GAME = 0;

    /** Streaming {details} */
    public const TYPE_STREAMING = 1;

    /** Listening to {name} */
    public const TYPE_LISTENING = 2;

    /** Watching {name} */
    public const TYPE_WATCHING = 3;

    /** {emoji} {name} */
    public const TYPE_CUSTOM = 4;

    /** Competing in {name} */
    public const TYPE_COMPETING = 5;

    /** @deprecated 10.0.0 Use `Activity::TYPE_GAME` */
    public const TYPE_PLAYING = self::TYPE_GAME;

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
     * {@inheritDoc}
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
     *
     * @throws \Exception
     */
    protected function getCreatedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['created_at'])) {
            return null;
        }

        return Carbon::createFromTimestamp($this->attributes['created_at']);
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
            case self::TYPE_GAME:
                return 'Playing '.$this->name;
            case self::TYPE_STREAMING:
                return 'Streaming '.$this->details;
            case self::TYPE_LISTENING:
                return 'Listening to '.$this->name;
            case self::TYPE_WATCHING:
                return 'Watching '.$this->name;
            case self::TYPE_CUSTOM:
                return "{$this->emoji} {$this->state}";
            case self::TYPE_COMPETING:
                return 'Competing in '.$this->name;
        }

        return $this->name;
    }
}
