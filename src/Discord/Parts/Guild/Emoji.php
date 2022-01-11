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

/**
 * An emoji object represents a custom emoji.
 *
 * @property string            $id             The identifier for the emoji.
 * @property string            $name           The name of the emoji.
 * @property Guild             $guild          The guild that owns the emoji.
 * @property string            $guild_id       The identifier of the guild that owns the emoji.
 * @property bool              $managed        Whether this emoji is managed by a role.
 * @property bool              $require_colons Whether the emoji requires colons to be triggered.
 * @property Collection|Role[] $roles          The roles that are allowed to use the emoji.
 * @property User|null         $user           user that created this emoji.
 * @property bool              $animated       Whether the emoji is animated.
 * @property bool              $available      Whether this emoji can be used, may be false due to loss of Server Boosts.
 */
class Emoji extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'name', 'guild_id', 'managed', 'require_colons', 'roles', 'user', 'animated', 'available'];

    /**
     * Returns the guild attribute.
     *
     * @return Guild The guild the emoji belongs to.
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
    public function __toString()
    {
        if ($this->id) {
            return '<'.($this->animated ? 'a' : '').$this->toReactionString().'>';
        }

        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [];
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
