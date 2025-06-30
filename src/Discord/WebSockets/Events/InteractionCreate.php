<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Helpers\RegisteredCommand;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option as RequestOption;
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

        foreach ($interaction->data->resolved->users ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $this->discord->users->pushItem($this->discord->users->create($user, true));
            }
        }

        if (isset($interaction->member)) {
            // Do not load guild from cache as it may delay interaction codes.
            /** @var ?Guild */
            if ($guild = $this->discord->guilds->offsetGet($interaction->guild_id)) {
                $members = $guild->members;

                foreach ($interaction->data->resolved->members ?? [] as $snowflake => $member) {
                    $this->cacheMember($members, (array) $member + ['user' => $interaction->data->resolved->users->$snowflake]);
                }

                $this->cacheMember($members, (array) $interaction->member);
            }

            // User caching from member
            $this->cacheUser($interaction->member->user);
        }

        if (isset($interaction->user)) {
            // User caching from user dm
            $this->cacheUser($interaction->user);
        }

        if (isset($interaction->entitlements)) {
            foreach($interaction->entitlements as $entitlement) {
                if ($entitlementPart = $this->discord->application->entitlements->get('id', $entitlement->id)) {
                    $entitlementPart->fill((array) $entitlement);
                } else {
                    $this->discord->application->entitlements->set($entitlement->id, $this->discord->application->entitlements->create($entitlement, true));
                }
            }
        }

        if ($interaction->type == Interaction::TYPE_APPLICATION_COMMAND) {
            $command = $interaction->data;
            if (isset($this->discord->application_commands[$command->name])) {
                $this->discord->application_commands[$command->name]->execute($command->options ?? [], $interaction);
            }
        } elseif ($interaction->type == Interaction::TYPE_APPLICATION_COMMAND_AUTOCOMPLETE) {
            $command = $interaction->data;
            if (isset($this->discord->application_commands[$command->name])) {
                $this->checkCommand($this->discord->application_commands[$command->name], $command->options, $interaction);
            }
        }

        return $interaction;
    }

    /**
     * Recursively checks and handles command options for an interaction.
     *
     * @param RegisteredCommand                          $command    The command or subcommand to check.
     * @param ExCollectionInterface|RequestOption[]|null $options    The list of options to process.
     * @param Interaction                                $interaction The interaction instance from Discord.
     *
     * @return bool Returns true if a suggestion was triggered, otherwise false.
     */
    protected function checkCommand(RegisteredCommand $command, $options, Interaction $interaction): bool
    {
        foreach ($options as $option) {
            /** @var ?RegisteredCommand $subCommand */
            if ($subCommand = $command->getSubCommand($option->name)) {
                if (isset($option->focused) && $option->focused) {
                    return $subCommand->suggest($interaction);
                }
                if (! empty($option->options)) {
                    return $this->checkCommand($subCommand, $option->options, $interaction);
                }
            } elseif (isset($option->focused) && $option->focused) {
                return $command->suggest($interaction);
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    protected function cacheMember(MemberRepository $members, array $memberdata): void
    {
        // Do not load members from cache as it may delay interaction codes.
        if ($member = $members->offsetGet($memberdata['user']->id)) {
            $member->fill($memberdata);
        } else {
            $members->pushItem($members->create($memberdata, true));
        }
    }
}
