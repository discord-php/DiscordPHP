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

use Discord\Helpers\Deferred;
use Discord\InteractionType;
use Discord\Parts\Interactions\Command\Command;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;

class InteractionCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $interaction = $this->factory->create(Interaction::class, $data, true);

        if ($interaction->type == InteractionType::APPLICATION_COMMAND) {
            $checkCommand = function ($command) use ($interaction, &$checkCommand) {
                // Tries to cache the command
                // possibly the laziest thing ive ever done - stdClass -> array
                $cmd = json_decode(json_encode($command), true);
                if (! isset($cmd['id'])) {
                    $cmd['id'] = $interaction->data->id;
                }
                if (! isset($cmd['application_id'])) {
                    $cmd['application_id'] = $interaction->application_id;
                }
                if ($interaction->guild_id) {
                    if (! $interaction->guild->commands->get('id', $cmd['id'])) {
                        $interaction->guild->commands->offsetSet($cmd['id'], $this->factory->create(Command::class, $cmd, true));

                        var_dump($interaction->guild->commands->get('id', $cmd['id']));
                    }
                } else {
                    if (! $this->discord->commands->get('id', $cmd['id'])) {
                        $this->discord->commands->offsetSet($cmd['id'], $this->factory->create(Command::class, $cmd, true));
                    }
                }

                if (isset($this->discord->registered_commands[$command['name']])) {
                    if ($this->discord->registered_commands[$command['name']]->execute($command['options'] ?? [], $interaction)) {
                        return true;
                    }
                }

                foreach ($command['options'] ?? [] as $option) {
                    if ($checkCommand($option)) {
                        return true;
                    }
                }
            };

            $checkCommand($interaction->data);
        }

        $deferred->resolve($interaction);
    }
}
