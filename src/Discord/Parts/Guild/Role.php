<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Permissions\RolePermission as Permission;
use Discord\Parts\Part;

/**
 * A role defines permissions for the guild. Members can be added to the role. The role belongs to a guild.
 *
 * @property string  $id
 * @property string  $name
 * @property int $color
 * @property bool    $managed
 * @property bool    $hoist
 * @property int     $position
 * @property int     $permissions
 * @property string  $guild_id
 */
class Role extends Part
{
    /**
     * {@inheritdoc}
     */
    public $findable = false;

    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'color', 'managed', 'hoist', 'position', 'permissions', 'guild_id'];

    /**
     * Runs extra construction tasks.
     *
     * @return void
     */
    public function afterConstruct()
    {
        if (! $this->created) {
            $this->permissions = new Permission();

            if (isset($this->guild_id)) {
                $this->save();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected $uris = [
        'get'    => '',
        'create' => 'guilds/:guild_id/roles',
        'update' => 'guilds/:guild_id/roles/:id',
        'delete' => 'guilds/:guild_id/roles/:id',
    ];

    /**
     * Sets the permissions attribute.
     *
     * @param Permission|int $permission The Permissions that you want to set.
     *
     * @return bool Whether the setting succeeded or failed.
     */
    public function setPermissionsAttribute($permission)
    {
        if (! $permission instanceof Permission) {
            return false;
        }

        $this->attributes['permissions'] = $permission;

        return true;
    }

    /**
     * Sets the color for a role. RGB.
     *
     * @param int $red   The red value in RGB.
     * @param int $green The green value in RGB.
     * @param int $blue  The blue value in RGB.
     *
     * @return bool Whether the setting succeeded or failed.
     */
    public function setColor($red = null, $green = null, $blue = null)
    {
        if (is_null($red)) {
            $this->setAttribute('color', 0);

            return true;
        }

        $this->setAttribute('color', "{$red}{$green}{$blue}");

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes()
    {
        return [
            'name'        => $this->name,
            'hoist'       => $this->hoist,
            'color'       => $this->color,
            'permissions' => $this->permissions->perms,
        ];
    }
}
