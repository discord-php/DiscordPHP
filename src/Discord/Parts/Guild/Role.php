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

class Role extends Part
{
    /**
     * Is the part findable?
     *
     * @var bool
     */
    public $findable = false;

    /**
     * The parts fillable attributes.
     *
     * @var array
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
     * URIs used to get/create/update/delete the part.
     *
     * @var array
     */
    protected $uris = [
        'get' => '',
        'create' => 'guilds/:guild_id/roles',
        'update' => 'guilds/:guild_id/roles/:id',
        'delete' => 'guilds/:guild_id/roles/:id',
    ];

    /**
     * Sets the permissions attribute.
     *
     * @param Permission|int $permission
     *
     * @return bool
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
     * @param int $red
     * @param int $green
     * @param int $blue
     *
     * @return bool
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
     * Returns the attributes needed to create.
     *
     * @return array
     */
    public function getCreatableAttributes()
    {
        return [];
    }

    /**
     * Returns the attributes needed to edit.
     *
     * @return array
     */
    public function getUpdatableAttributes()
    {
        return [
            'name' => $this->name,
            'hoist' => $this->hoist,
            'color' => $this->color,
            'permissions' => $this->permissions->perms,
        ];
    }
}
