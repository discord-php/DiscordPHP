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
use Discord\Helpers\Deferred;
use Discord\Parts\Interactions\Command\Overwrite;

/**
 * @see https://discord.com/developers/docs/topics/gateway#integration-update
 */
class ApplicationCommandPermissionsUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $overwritePart = $oldOverwrite = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($command = $guild->commands->get('id', $data->id)) {
                // There is only one command permissions object
                if ($oldOverwrite = $command->overwrites->first()) {
                    // Swap
                    $overwritePart = $oldOverwrite;
                    $oldOverwrite = clone $oldOverwrite;

                    $overwritePart->fill((array) $data);
                }
            }
        }

        if (! $overwritePart) {
            /** @var Overwrite */
            $overwritePart = $this->factory->create(Overwrite::class, $data, true);
            if ($guild && isset($command)) {
                // There is only one command permissions object
                $command->overwrites->clear();
                $command->overwrites->pushItem($overwritePart);
            }
        }

        // TODO: Add documentation
        $deferred->resolve([$overwritePart, $oldOverwrite]);
    }
}
