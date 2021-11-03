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
use Discord\Parts\Permissions\RolePermission;

/**
 * A role defines permissions for the guild. Members can be added to the role. The role belongs to a guild.
 *
 * @property string         $id            The unique identifier of the role.
 * @property string         $name          The name of the role.
 * @property int            $color         The color of the guild.
 * @property bool           $managed       Whether the role is managed by a Twitch subscriber feature.
 * @property bool           $hoist         Whether the role is hoisted on the sidebar.
 * @property int            $position      The position of the role on the sidebar.
 * @property RolePermission $permissions   The permissions of the role.
 * @property string         $icon          The URL to the role icon.
 * @property string         $icon_hash     The icon hash for the role.
 * @property string         $unicode_emoji The unicode emoji for the role.
 * @property bool           $mentionable   Whether the role is mentionable.
 * @property Guild          $guild         The guild that the role belongs to.
 * @property string         $guild_id      The unique identifier of the guild that the role belongs to.
 */
class Role extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'name', 'color', 'managed', 'hoist', 'position', 'permissions', 'icon', 'unicode_emoji', 'mentionable', 'guild_id'];

    /**
     * @inheritdoc
     */
    protected function afterConstruct(): void
    {
        if (! isset($this->attributes['permissions'])) {
            $this->permissions = $this->factory->create(RolePermission::class);
        }
    }

    /**
     * Gets the guild attribute.
     *
     * @return Guild The guild attribute.
     */
    protected function getGuildAttribute(): Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Sets the permissions attribute.
     *
     * @param  RolePermission|int $permission The permissions to set.
     * @throws \Exception
     */
    protected function setPermissionsAttribute($permission): void
    {
        if (! ($permission instanceof RolePermission)) {
            $permission = $this->factory->create(RolePermission::class, ['bitwise' => $permission], true);
        }

        $this->attributes['permissions'] = $permission;
    }

    /**
     * Sets the color for a role. RGB.
     *
     * @param int $red   The red value in RGB.
     * @param int $green The green value in RGB.
     * @param int $blue  The blue value in RGB.
     */
    public function setColor(int $red = 0, int $green = 0, int $blue = 0)
    {
        $this->color = ($red * 16 ** 4 + $green * 16 ** 2 + $blue);
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * Returns the role icon.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the role icon or null.
     */
    public function getIconAttribute(string $format = 'jpg', int $size = 64)
    {
        if (is_null($this->attributes['icon'])) {
            return null;
        }

        if (false === array_search($format, ['png', 'jpg', 'webp'])) {
            $format = 'jpg';
        }

        return "https://cdn.discordapp.com/role-icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the role icon hash.
     *
     * @return string|null The role icon hash or null.
     */
    protected function getIconHashAttribute()
    {
        return $this->attributes['icon'];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'hoist' => $this->hoist,
            'color' => $this->color,
            'permissions' => $this->permissions->bitwise,
            'icon' => $this->attributes['icon'],
            'unicode_emoji' => $this->unicode_emoji,
            'mentionable' => $this->mentionable,
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'role_id' => $this->id,
        ];
    }

    /**
     * Returns a formatted mention.
     *
     * @return string A formatted mention.
     */
    public function __toString()
    {
        return "<@&{$this->id}>";
    }
}
