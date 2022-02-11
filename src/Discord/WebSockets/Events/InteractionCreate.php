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
use Discord\Parts\User\User;
use Discord\WebSockets\Event;

/**
 * @see https://discord.com/developers/docs/topics/gateway#interaction-create
 */
class InteractionCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $interaction = $this->factory->create(Interaction::class, $data, true);

        foreach ($data->data->resolved->users ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $user, true));
            }
        }

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
        } elseif ($interaction->type == InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE) {
            if (isset($this->discord->application_commands[$interaction->data['name']])) {
                if ($this->discord->application_commands[$interaction->data['name']]->suggest($interaction)) {
                    return;
                }
            }
        }

        if (isset($data->member->user)) {
            // User caching from member
            $this->cacheUser($data->member->user);
        }

        if (isset($data->user)) {
            // User caching from user dm
            $this->cacheUser($data->user);
        }

        $deferred->resolve($interaction);
    }
}
