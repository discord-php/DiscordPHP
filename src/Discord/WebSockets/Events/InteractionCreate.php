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
                if (isset($this->discord->application_commands[$command['name']])) {
                    if ($this->discord->application_commands[$command['name']]->execute($command['options'] ?? [], $interaction)) {
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
