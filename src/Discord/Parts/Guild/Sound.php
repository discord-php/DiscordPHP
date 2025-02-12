<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\User;
use Stringable;

/**
 * An sound object represents a soundboard sound.
 *
 * @link https://discord.com/developers/docs/resources/soundboard
 *
 * @since 10.0.0
 *
 * @property string            $name        The name of this sound.
 * @property ?string           $sound_id    The identifier for this sound.
 * @property double            $volume      The volume of this sound, from 0 to 1.
 * @property ?string           $emoji_id    The identifier for this sound's custom emoji.
 * @property ?string           $emoji_name  The unicode character of this sound's standard emoji.
 * @property ?string           $guild_id    The identifier of the guild this sound is in.
 * @property bool              $available   Whether this sound can be used, may be false due to loss of Server Boosts.
 * @property User|null         $user        The user who created this sound.
 * @property-read Guild|null   $guild       The guild that owns the sound.
 */
class Sound extends Part implements Stringable
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'name',
        'sound_id',
        'volume',
        'emoji_id',
        'emoji_name',
        'guild_id',
        'available',
        'user',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild the sound belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the user that created the sound.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the URL of the sound.
     *
     * @return string|null
     */
    public function getURL(): ?string
    {
        if (! isset($this->sound_id)) {
            return null;
        }

        return "https://cdn.discordapp.com/soundboard-sounds/{$this->sound_id}";
    }

    /**
     * Converts the sound to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/soundboard#modify-guild-soundboard-sound
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'volume' => $this->volume,
            'emoji_id' => $this->emoji_id,
            'emoji_name' => $this->emoji_name,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'sound_id' => $this->sound_id,
        ];
    }
}
