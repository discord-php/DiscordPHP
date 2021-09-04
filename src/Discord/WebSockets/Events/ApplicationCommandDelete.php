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
use Discord\Parts\Interactions\Command\Command;

class ApplicationCommandDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $command = null;

        if ($data->guild_id) {
            if ($guild = $this->discord->guilds->commands->get('id', $data->guild_id)) {
                $command = $guild->commands->get('id', $data->id);
                $guild->commands->pull($data->id);
            }
        } else {
            $command = $this->discord->application->commands->get('id', $data->id);
            $this->discord->application->commands->pull($data->id);
        }

        if (! $command) {
            $command = $this->factory->create(Command::class, $data, true);
        }

        $deferred->resolve($command);
    }
}
