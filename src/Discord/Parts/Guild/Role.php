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

use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission as Permission;
use React\Promise\Deferred;

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
        }
    }

    /**
     * Sets the permissions attribute.
     *
     * @param Permission|int $permission The Permissions that you want to set.
     *
     * @return \React\Promise\Promise
     */
    public function setPermissionsAttribute($permission)
    {
        $deferred = new Deferred();

        if (! $permission instanceof Permission) {
            $deferred->reject(new \Exception('$permission must be an instance of Permission.'));

            return $deferred->promise();
        }

        $this->attributes['permissions'] = $permission;

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * Sets the color for a role. RGB.
     *
     * @param int $red   The red value in RGB.
     * @param int $green The green value in RGB.
     * @param int $blue  The blue value in RGB.
     *
     * @return \React\Promise\Promise
     */
    public function setColor($red = null, $green = null, $blue = null)
    {
        $deferred = new Deferred();

        if (is_null($red)) {
            $this->setAttribute('color', 0);

            $deferred->resolve();
            return $deferred->promise();
        }

        $this->setAttribute('color', "{$red}{$green}{$blue}");

        $deferred->resolve();
        return $deferred->promise();
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
