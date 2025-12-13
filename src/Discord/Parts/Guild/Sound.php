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

namespace Discord\Parts\Guild;

use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Repository\Guild\SoundRepository;
use React\Promise\PromiseInterface;
use Stringable;

use function React\Promise\reject;

/**
 * An sound object represents a soundboard sound.
 *
 * Users can play soundboard sounds in voice channels, triggering a Voice Channel Effect Send Gateway event for users connected to the voice channel.
 * There is a set of default sounds available to all users. Soundboard sounds can also be created in a guild; users will be able to use the sounds in the guild, and Nitro subscribers can use them in all guilds.
 * Soundboard sounds in a set of guilds can be retrieved over the Gateway using Request Soundboard Sounds.
 *
 * @link https://discord.com/developers/docs/resources/soundboard
 *
 * @since 10.0.0
 *
 * @property      string       $name       The name of this sound.
 * @property      string       $sound_id   The identifier for this sound.
 * @property      float        $volume     The volume of this sound, from 0 to 1.
 * @property      ?string|null $emoji_id   The identifier for this sound's custom emoji.
 * @property      ?string|null $emoji_name The unicode character of this sound's standard emoji.
 * @property      string|null  $guild_id   The identifier of the guild this sound is in.
 * @property-read Guild|null   $guild      The guild that owns the sound.
 * @property      bool         $available  Whether this sound can be used, may be false due to loss of Server Boosts.
 * @property      User|null    $user       The user who created this sound.
 */
class Sound extends Part implements Stringable
{
    /**
     * @inheritDoc
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

        return $this->attributePartHelper('user', User::class);
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
     * @inheritDoc
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
     * Gets the originating repository of the part.
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return SoundRepository|null The repository, or null if required part data is missing.
     */
    public function getRepository(): SoundRepository|null
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        /** @var Guild $guild */
        $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);

        return $guild->sounds;
    }

    /**
     * @inheritDoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (isset($this->attributes['guild_id'])) {
            /** @var Guild $guild */
            $guild = $this->guild ?? $this->factory->part(Guild::class, ['id' => $this->attributes['guild_id']], true);
            if ($botperms = $guild->getBotPermissions()) {
                if ($this->created) {
                    if ($this->user->id === $this->discord->id) {
                        if (! $botperms->create_guild_expressions && ! $botperms->manage_guild_expressions) {
                            return reject(new NoPermissionsException("You do not have permission to save changes to the sound {$this->id} in guild {$guild->id}."));
                        }
                    } else {
                        if (! $botperms->manage_guild_expressions) {
                            return reject(new NoPermissionsException("You do not have permission to save changes to the sound {$this->id} in guild {$guild->id}."));
                        }
                    }
                } elseif (! $botperms->create_guild_expressions) {
                    return reject(new NoPermissionsException("You do not have permission to save the sound {$this->id} in guild {$guild->id}."));
                }
            }

            return $guild->sounds->save($this, $reason);
        }

        return parent::save($reason);
    }

    /**
     * @inheritDoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'sound_id' => $this->sound_id,
        ];
    }
}
