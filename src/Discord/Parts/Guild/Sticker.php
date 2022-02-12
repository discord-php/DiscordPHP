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

/**
 * A sticker that can be sent in a Discord message.
 *
 * @see https://discord.com/developers/docs/resources/sticker#sticker-object-sticker-structure
 *
 * @property string      $id          The identifier for the sticker.
 * @property string|null $pack_id     For standard stickers, id of the pack the sticker is from.
 * @property string      $name        The name of the sticker.
 * @property string      $description The description of the sticker.
 * @property array       $tags        Autocomplete/suggestion tags for the sticker (max 200 characters).
 * @property int         $type        The type of sticker.
 * @property int         $format_type The type of sticker format.
 * @property bool|null   $available   Whether this guild sticker can be used, may be false due to loss of Server Boosts.
 * @property string|null $guild_id    The identifier of the guild that owns the sticker.
 * @property Guild|null  $guild       The guild that owns the sticker.
 * @property User|null   $user        The user that uploaded the guild sticker.
 * @property int|null    $sort_value  The standard sticker's sort order within its pack.
 */
class Sticker extends Part
{
    public const TYPE_STANDARD = 1;
    public const TYPE_GUILD = 2;

    public const FORMAT_TYPE_PNG = 1;
    public const FORMAT_TYPE_APNG = 2;
    public const FORMAT_TYPE_LOTTIE = 3;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'tags',
        'asset',
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
     * @inheritdoc
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
    protected function getUserAttribute(): ?Part
    {
        if (! isset($this->attributes['user'])) {
            return null;
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, $this->attributes['user'], true);
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

        if ($this->attributes['format_type'] == self::FORMAT_TYPE_LOTTIE) {
            $format = 'lottie';
        }

        return "https://cdn.discordapp.com/stickers/{$this->id}.{$format}";
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description ?? null,
            'tags' => $this->attributes['tags'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        if ($this->type == self::TYPE_GUILD) {
            return [
                'sticker_id' => $this->id,
                'guild_id' => $this->guild_id,
            ];
        }

        return [
            'sticker_id' => $this->id,
        ];
    }
}
