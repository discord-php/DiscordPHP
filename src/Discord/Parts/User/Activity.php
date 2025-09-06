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

namespace Discord\Parts\User;

use Carbon\Carbon;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Part;
use Stringable;

/**
 * The Activity part describes activities the member is undertaking.
 *
 * @link https://discord.com/developers/docs/topics/gateway-events#activity-object
 *
 * @since 5.0.0 Renamed from Game to Activity
 * @since 3.2.2
 *
 * @property string        $name                The activity's name.
 * @property int           $type                Activity type.
 * @property ?string|null  $url                 Stream url, is validated when type is 1.
 * @property Carbon|null   $created_at          Timestamp of when the activity was added to the user's session.
 * @property object|null   $timestamps          Unix timestamps for start and/or end of the game.
 * @property string|null   $application_id      Application id for the game.
 * @property ?int|null     $status_display_type Status display type; controls which field is displayed in the user's status text in the member list.
 * @property ?string|null  $details             What the player is currently doing.
 * @property ?string|null  $details_url         URL that is linked when clicking on the details text
 * @property ?string|null  $state               The user's current party status, or text used for a custom status.
 * @property ?string|null  $state_url           URL that is linked when clicking on the state text.
 * @property Emoji|null    $emoji               The emoji used for a custom status.
 * @property Party|null    $party               Information for the current party of the player.
 * @property Assets|null   $assets              Images for the presence and their hover texts.
 * @property Secrets|null  $secrets             Secrets for Rich Presence joining and spectating.
 * @property bool|null     $instance            Whether or not the activity is an instanced game session.
 * @property int|null      $flags               Activity flags `OR`d together, describes what the payload includes.
 * @property object[]|null $buttons             The custom buttons shown in the Rich Presence (max 2).
 */
class Activity extends Part implements Stringable
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

    public const STATUS_DISPLAY_TYPE_NAME = 0;
    public const STATUS_DISPLAY_TYPE_STATE = 1;
    public const STATUS_DISPLAY_TYPE_DETAILS = 2;

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
        'status_display_type',
        'details',
        'details_url',
        'state',
        'state_url',
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
        return $this->attributePartHelper('emoji', Emoji::class);
    }

    /**
     * Gets the party object of the activity.
     *
     * @return Party|null
     */
    protected function getPartyAttribute(): ?Party
    {
        return $this->attributePartHelper('party', Party::class);
    }

    /**
     * Gets the assets object of the activity.
     *
     * @return Assets|null
     */
    protected function getAssetsAttribute(): ?Assets
    {
        return $this->attributePartHelper('assets', Assets::class);
    }

    /**
     * Converts the activity to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return match ($this->type) {
            self::TYPE_GAME => 'Playing '.$this->name,
            self::TYPE_STREAMING => 'Streaming '.$this->details,
            self::TYPE_LISTENING => 'Listening to '.$this->name,
            self::TYPE_WATCHING => 'Watching '.$this->name,
            self::TYPE_CUSTOM => "{$this->emoji} {$this->state}",
            self::TYPE_COMPETING => 'Competing in '.$this->name,
            default => $this->name,
        };
    }
}
