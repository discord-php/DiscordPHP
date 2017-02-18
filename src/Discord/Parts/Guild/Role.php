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
use Discord\Parts\Permissions\RolePermission;
use React\Promise\Deferred;

/**
 * A role defines permissions for the guild. Members can be added to the role. The role belongs to a guild.
 *
 * @property string                                    $id          The unique identifier of the role.
 * @property string                                    $name        The name of the role.
 * @property int                                       $color       The color of the guild.
 * @property bool                                      $managed     Whether the role is managed by a Twitch subscriber feature.
 * @property bool                                      $hoist       Whether the role is hoisted on the sidebar.
 * @property int                                       $position    The position of the role on the sidebar.
 * @property \Discord\Parts\Permissions\RolePermission $permissions The permissions of the role.
 * @property bool                                      $mentionable Whether the role is mentionable.
 * @property \Discord\Parts\Guild\Guild                $guild       The guild that the role belongs to.
 * @property string                                    $guild_id    The unique identifier of the guild that the role belongs to.
 */
class Role extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'color', 'managed', 'hoist', 'position', 'permissions', 'mentionable', 'guild_id'];

    /**
     * Runs extra construction tasks.
     *
     * @return void
     */
    public function afterConstruct()
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
    public function getGuildAttribute()
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Sets the permissions attribute.
     *
     * @param RolePermission|int $permission The permissions to set.
     *
     * @return void
     */
    public function setPermissionsAttribute($permission)
    {
        if (! ($permission instanceof RolePermission)) {
            $permissionPart = $this->factory->create(RolePermission::class);
            $permissionPart->decodeBitwise($permission);

            $permission = $permissionPart;
        }

        $this->attributes['permissions'] = $permission;
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

        $this->setAttribute('color', $red * 16 ** 4 + $green * 16 ** 2 + $blue);

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
            'permissions' => $this->permissions->bitwise,
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
