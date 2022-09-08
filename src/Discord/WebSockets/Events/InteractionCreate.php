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
        /** @var Interaction */
        $interaction = $this->factory->part(Interaction::class, (array) $data, true);

        foreach ($data->data->resolved->users ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $user, true));
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

        if ($data->type == InteractionType::APPLICATION_COMMAND) {
            $command = $data->data;
            if (isset($this->discord->application_commands[$command->name])) {
                if ($this->discord->application_commands[$command->name]->execute($command->options ?? [], $interaction)) {
                    return;
                }
            }
        } elseif ($data->type == InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE) {
            $command = $data->data;
            if (isset($this->discord->application_commands[$command->name])) {
                $checkCommand = function ($command, $options) use (&$checkCommand, $interaction) {
                    foreach ($options as $option) {
                        if ($subCommand = $command->getSubCommand($option->name)) {
                            if (! empty($option->focused)) {
                                return $subCommand->suggest($interaction);
                            }
                            if (! empty($option->options)) {
                                return $checkCommand($subCommand, $option->options);
                            }
                        } elseif (! empty($option->focused)) {
                            return $command->suggest($interaction);
                        }
                    }

                    return false;
                };
                if ($checkCommand($this->discord->application_commands[$command->name], $command->options)) {
                    return;
                }
            }
        }

        $deferred->resolve($interaction);
    }
}
