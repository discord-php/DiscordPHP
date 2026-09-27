<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Sound;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * An emoji reaction or a soundboard sound sent in a voice channel the bot is connected to, from the
 * `VOICE_CHANNEL_EFFECT_SEND` event.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#voice-channel-effect-send
 *
 * @since 10.60.0
 *
 * @property      string          $channel_id     The voice channel the effect was sent in.
 * @property-read Channel|null    $channel        The voice channel the effect was sent in.
 * @property      string          $guild_id       The guild the effect was sent in.
 * @property-read Guild|null      $guild          The guild the effect was sent in.
 * @property      string          $user_id        The user who sent the effect.
 * @property-read User|null       $user           The user who sent the effect.
 * @property-read Member|null     $member         The user who sent the effect, as a member of the guild.
 * @property      ?Emoji|null     $emoji          The emoji sent, for emoji reactions and soundboard sounds.
 * @property      ?int|null       $animation_type The emoji's animation: {@see self::ANIMATION_TYPE_PREMIUM} or {@see self::ANIMATION_TYPE_BASIC}.
 * @property      ?int|null       $animation_id   The ID of the emoji's animation.
 * @property      string|int|null $sound_id       The soundboard sound, for soundboard effects.
 * @property-read Sound|null      $sound          The soundboard sound, when the guild's sounds or the default ones are cached.
 * @property      ?float|null     $sound_volume   The sound's volume, from 0 to 1.
 */
class VoiceChannelEffect extends Part
{
    /** A fun animation, sent by a Nitro subscriber. */
    public const ANIMATION_TYPE_PREMIUM = 0;

    /** The standard animation. */
    public const ANIMATION_TYPE_BASIC = 1;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'channel_id',
        'guild_id',
        'user_id',
        'emoji',
        'animation_type',
        'animation_id',
        'sound_id',
        'sound_volume',
    ];

    /**
     * Returns the guild the effect was sent in.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the voice channel the effect was sent in.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        return $this->guild?->channels->get('id', $this->channel_id);
    }

    /**
     * Returns the user who sent the effect.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Returns the user who sent the effect, as a member of the guild.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        return $this->guild?->members->get('id', $this->user_id);
    }

    /**
     * Returns the emoji sent.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        return $this->attributePartHelper('emoji', Emoji::class);
    }

    /**
     * Returns the soundboard sound played: one of the guild's, or one of Discord's default sounds.
     *
     * @return Sound|null
     */
    protected function getSoundAttribute(): ?Sound
    {
        if (! isset($this->attributes['sound_id'])) {
            return null;
        }

        $sound_id = (string) $this->attributes['sound_id'];

        return $this->guild?->sounds->get('sound_id', $sound_id) ?? $this->discord->sounds->get('sound_id', $sound_id);
    }
}
