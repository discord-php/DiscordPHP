<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\Permissions\RolePermission;
use Discord\Helpers\Deferred;
use React\Promise\PromiseInterface;

/**
 * A role defines permissions for the guild. Members can be added to the role. The role belongs to a guild.
 *
 * @property string         $id          The unique identifier of the role.
 * @property string         $name        The name of the role.
 * @property int            $color       The color of the guild.
 * @property bool           $managed     Whether the role is managed by a Twitch subscriber feature.
 * @property bool           $hoist       Whether the role is hoisted on the sidebar.
 * @property int            $position    The position of the role on the sidebar.
 * @property RolePermission $permissions The permissions of the role.
 * @property bool           $mentionable Whether the role is mentionable.
 * @property Guild          $guild       The guild that the role belongs to.
 * @property string         $guild_id    The unique identifier of the guild that the role belongs to.
 */
class Role extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = ['id', 'name', 'color', 'managed', 'hoist', 'position', 'permissions', 'mentionable', 'guild_id'];

    /**
     * {@inheritdoc}
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
     * @param int|null $red   The red value in RGB.
     * @param int|null $green The green value in RGB.
     * @param int|null $blue  The blue value in RGB.
     *
     * @return PromiseInterface
     */
    public function setColor(?int $red = null, ?int $green = null, ?int $blue = null): PromiseInterface
    {
        $deferred = new Deferred();

        if (is_null($red)) {
            $this->color = 0;

            $deferred->resolve();

            return $deferred->promise();
        }

        $this->color = ($red * 16 ** 4 + $green * 16 ** 2 + $blue);

        $deferred->resolve();

        return $deferred->promise();
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatableAttributes(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'hoist' => $this->hoist,
            'color' => $this->color,
            'permissions' => $this->permissions->bitwise,
            'mentionable' => $this->mentionable,
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
