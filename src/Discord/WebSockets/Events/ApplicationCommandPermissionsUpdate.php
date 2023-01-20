<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\Guild\CommandPermissions;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#integration-update
 *
 * @since 7.1.0
 */
class ApplicationCommandPermissionsUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $commandPermissionsPart = $oldCommandPermissions = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?CommandPermissions */
            if ($oldCommandPermissions = yield $guild->command_permissions->cacheGet($data->id)) {
                // Swap
                $commandPermissionsPart = $oldCommandPermissions;
                $oldCommandPermissions = clone $oldCommandPermissions;

                $commandPermissionsPart->fill((array) $data);

                if ($data->id === $data->application_id) {
                    // Permission synced
                    yield $guild->command_permissions->cache->delete($oldCommandPermissions->id);
                }
            }
        }

        if ($commandPermissionsPart === null) {
            /** @var CommandPermissions */
            $commandPermissionsPart = $this->factory->part(CommandPermissions::class, (array) $data, true);
        }

        if (isset($guild) && $commandPermissionsPart) {
            // Permission set / updated
            $guild->command_permissions->set($data->id, $commandPermissionsPart);
        }

        return [$commandPermissionsPart, $oldCommandPermissions];
    }
}
