<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Helpers\Collection;
use Discord\Parts\Part;

/**
 * Guild Application Command Permissions Overwrite Class.
 *
 * @see https://discord.com/developers/docs/interactions/application-commands#application-command-permissions-object-guild-application-command-permissions-structure
 *
 * @property string                  $id             The id of the command or the application ID if no overwrites
 * @property string                  $application_id The id of the application the command belongs to
 * @property string                  $guild_id       The id of the guild
 * @property Collection|Permission[] $permissions    The permissions for the command in the guild
 */
class Overwrite extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'application_id', 'guild_id', 'permissions'];

    /**
     * Gets the permissions attribute.
     *
     * @return Collection|Permission[] A collection of permissions.
     */
    protected function getPermissionsAttribute()
    {
        $permissions = new Collection();

        foreach ($this->attributes['permissions'] ?? [] as $permission) {
            $permissions->pushItem($this->factory->create(Permission::class, $permission, true));
        }

        return $permissions;
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'permissions' => $this->attributes['permissions'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->application_id,
            'guild_id' => $this->guild_id,
        ];
    }

    /**
     * Get the permission ID constant for All Channels in the guild (i.e. guild_id - 1)
     * Requires GMP extension loaded on 32 bits PHP.
     *
     * @see https://discord.com/developers/docs/interactions/application-commands#application-command-permissions-object-application-command-permissions-constants
     *
     * @return string The permission ID for all channels (i.e. guild_id - 1)
     */
    final public function allChannelsConstant(): string
    {
        if (PHP_INT_SIZE === 4) {
            return (string) \gmp_sub($this->guild_id, 1);
        }

        return (string) ($this->guild_id - 1);
    }
}
