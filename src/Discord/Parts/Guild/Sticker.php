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

/**
 * A sticker that can be sent in a Discord message.
 *
 * @link https://discord.com/developers/docs/resources/sticker#sticker-object-sticker-structure
 *
 * @since 7.0.0 Namespace moved from Channel to Guild
 * @since 6.0.0
 *
 * @property      string      $id          The identifier for the sticker.
 * @property      string|null $pack_id     For standard stickers, id of the pack the sticker is from.
 * @property      string      $name        The name of the sticker.
 * @property      ?string     $description The description of the sticker.
 * @property      array       $tags        Autocomplete/suggestion tags for the sticker (max 200 characters).
 * @property      int         $type        The type of sticker.
 * @property      int         $format_type The type of sticker format.
 * @property      bool|null   $available   Whether this guild sticker can be used, may be false due to loss of Server Boosts.
 * @property      string|null $guild_id    The identifier of the guild that owns the sticker.
 * @property-read Guild|null  $guild       The guild that owns the sticker.
 * @property      User|null   $user        The user that uploaded the guild sticker.
 * @property      int|null    $sort_value  The standard sticker's sort order within its pack.
 */
class Sticker extends Part
{
    public const TYPE_STANDARD = 1;
    public const TYPE_GUILD = 2;

    public const FORMAT_TYPE_PNG = 1;
    public const FORMAT_TYPE_APNG = 2;
    public const FORMAT_TYPE_LOTTIE = 3;
    public const FORMAT_TYPE_GIF = 4;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'name',
        'tags',
        'type',
        'format_type',
        'description',
        'pack_id',
        'sort_value',
        'available',
        'guild_id',
        'user',
    ];

    /**
     * {@inheritDoc}
     */
    public function isPartial(): bool
    {
        $partial = array_filter($this->attributes, function ($var) {
            return isset($var);
        });

        sort($partial);

        return array_keys($partial) == ['format_type', 'name', 'id'];
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild the sticker belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (! isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the user that created the sticker.
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
     * Returns the tags attribute.
     *
     * @return array
     */
    protected function getTagsAttribute(): array
    {
        if ($this->attributes['tags'] ?? null) {
            return explode(', ', $this->attributes['tags']);
        }

        return [];
    }

    /**
     * Returns the URL for the sticker.
     *
     * @return string The URL to the sticker.
     */
    public function __toString(): string
    {
        $format = 'png';

        switch ($this->attributes['format_type']) {
            case self::FORMAT_TYPE_LOTTIE:
                $format = 'lottie';
                break;
            case self::FORMAT_TYPE_GIF:
                $format = 'gif';
                break;
        }

        return "https://cdn.discordapp.com/stickers/{$this->id}.{$format}";
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/sticker#modify-guild-sticker-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'description' => $this->description,
            'tags' => $this->attributes['tags'],
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        if ($this->type == self::TYPE_GUILD) {
            return [
                'guild_id' => $this->guild_id,
                'sticker_id' => $this->id,
            ];
        }

        return [
            'sticker_id' => $this->id,
        ];
    }
}
