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

use Discord\InteractionType;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Interaction;
use Discord\Repository\Guild\MemberRepository;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#interaction-create
 *
 * @since 6.0.0
 */
class InteractionCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Interaction */
        $interaction = $this->factory->part(Interaction::class, (array) $data, true);

        foreach ($data->data->resolved->users ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $this->discord->users->pushItem($this->discord->users->create($user, true));
            }
        }

        if (isset($data->member)) {
            // Do not load guild from cache as it may delay interaction codes.
            /** @var ?Guild */
            if ($guild = $this->discord->guilds->offsetGet($data->guild_id)) {
                $members = $guild->members;

                foreach ($data->data->resolved->members ?? [] as $snowflake => $member) {
                    $this->cacheMember($members, (array) $member + ['user' => $data->data->resolved->users->$snowflake]);
                }

                $this->cacheMember($members, (array) $data->member);
            }

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
                $this->discord->application_commands[$command->name]->execute($command->options ?? [], $interaction);
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
                $checkCommand($this->discord->application_commands[$command->name], $command->options);
            }
        }

        return $interaction;
    }

    /**
     * {@inheritDoc}
     */
    protected function cacheMember(MemberRepository $members, array $memberdata)
    {
        // Do not load members from cache as it may delay interaction codes.
        if ($member = $members->offsetGet($memberdata['user']->id)) {
            $member->fill($memberdata);
        } else {
            $members->pushItem($members->create($memberdata, true));
        }
    }
}
