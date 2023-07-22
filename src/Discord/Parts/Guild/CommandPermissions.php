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

use Discord\Helpers\BigInt;
use Discord\Helpers\Collection;
use Discord\Parts\Interactions\Command\Permission;
use Discord\Parts\Part;

/**
 * Guild Application Command Permissions Class.
 *
 * @link https://discord.com/developers/docs/interactions/application-commands#application-command-permissions-object-guild-application-command-permissions-structure
 *
 * @since 10.0.0 Refactored from Interactions\Command\Overwrite to Guild\CommandPermissions
 * @since 7.0.0
 *
 * @property      string                  $id             The id of the command or the application ID if no overwrites.
 * @property      string                  $application_id The id of the application the command belongs to.
 * @property      string                  $guild_id       The id of the guild.
 * @property-read Guild|null              $guild
 * @property      Collection|Permission[] $permissions    The permissions for the command in the guild.
 */
class CommandPermissions extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'application_id',
        'guild_id',
        'permissions',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the permissions attribute.
     *
     * @return Collection|Permission[] A collection of permissions.
     */
    protected function getPermissionsAttribute()
    {
        $permissions = Collection::for(Permission::class);

        foreach ($this->attributes['permissions'] ?? [] as $permission) {
            $permissions->pushItem($this->factory->part(Permission::class, (array) $permission, true));
        }

        return $permissions;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'permissions' => $this->attributes['permissions'],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->guild_id,
            'application_id' => $this->application_id,
            'command_id' => $this->id,
        ];
    }

    /**
     * Get the permission ID constant for All Channels in the guild (i.e. guild_id - 1)
     * Requires GMP extension loaded on 32 bits PHP.
     *
     * @link https://discord.com/developers/docs/interactions/application-commands#application-command-permissions-object-application-command-permissions-constants
     *
     * @return string The permission ID for all channels (i.e. guild_id - 1)
     */
    final public function allChannelsConstant(): string
    {
        return (string) BigInt::sub($this->guild_id, 1);
    }
}
