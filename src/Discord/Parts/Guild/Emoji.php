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

use Discord\Helpers\Collection;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * An emoji object represents a custom emoji.
 *
 * @see https://discord.com/developers/docs/resources/emoji
 *
 * @property string            $id             The identifier for the emoji.
 * @property string            $name           The name of the emoji.
 * @property Collection|Role[] $roles          The roles that are allowed to use the emoji.
 * @property User|null         $user           User that created this emoji.
 * @property bool              $require_colons Whether the emoji requires colons to be triggered.
 * @property bool              $managed        Whether this emoji is managed by a role.
 * @property bool              $animated       Whether the emoji is animated.
 * @property bool              $available      Whether this emoji can be used, may be false due to loss of Server Boosts.
 * @property string|null       $guild_id       The identifier of the guild that owns the emoji.
 * @property Guild|null        $guild          The guild that owns the emoji.
 */
class Emoji extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'name',
        'roles',
        'user',
        'require_colons',
        'managed',
        'animated',
        'available',
        'guild_id',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild the emoji belongs to.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the roles attribute.
     *
     * @return Collection A collection of roles for the emoji.
     */
    protected function getRolesAttribute(): Collection
    {
        if (! $this->guild) {
            return new Collection();
        }

        return $this->guild->roles->filter(function ($role) {
            return in_array($role->id, $this->attributes['roles']);
        });
    }

    /**
     * Gets the user that created the emoji.
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
     * Converts the emoji to the format required for creating a reaction.
     *
     * @return string
     */
    public function toReactionString(): string
    {
        if ($this->id) {
            return ($this->animated ? 'a' : '').":{$this->name}:{$this->id}";
        }

        return $this->name;
    }

    /**
     * Converts the emoji to a string.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->id) {
            return '<'.($this->animated ? 'a' : '').$this->toReactionString().'>';
        }

        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'roles' => $this->attributes['roles'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'emoji_id' => $this->id,
        ];
    }
}
