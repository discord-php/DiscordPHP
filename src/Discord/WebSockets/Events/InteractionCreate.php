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
use Discord\Parts\Interactions\ApplicationCommand;
use Discord\Parts\Interactions\ApplicationCommandAutocomplete;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\ApplicationCommandData;
use Discord\Parts\Interactions\Request\Option as RequestOption;
use Discord\Parts\User\Member as UserMember;
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
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var Interaction */
        $interaction = $this->factory->part(Interaction::TYPES[$data->type ?? 0], (array) $data, true);

        foreach ($interaction->data->resolved->users ?? [] as $snowflake => $user) {
            if ($userPart = $this->discord->users->get('id', $snowflake)) {
                $userPart->fill((array) $user);
            } else {
                $this->discord->users->pushItem($this->discord->users->create($user, true));
            }
        }

        if ($interaction->member) {
            // Do not load guild from cache as it may delay interaction codes.
            /** @var ?Guild $guild */
            if ($guild = $this->discord->guilds->offsetGet($interaction->guild_id)) {
                /** @var Guild $guild */
                $members = $guild->members;

                foreach ($interaction->data->resolved->members ?? [] as $snowflake => $member) {
                    $this->cacheMember($members, (array) $member + ['user' => $interaction->data->resolved->users->$snowflake]);
                }

                $this->cacheMember($members, (array) $interaction->member);
            }

            // User caching from member
            if ($interaction->member->user) {
                $this->cacheUser($interaction->member->user);
            }
        }

        if ($interaction->user) {
            // User caching from user dm
            $this->cacheUser($interaction->user);
        }

        if ($interaction->entitlements) {
            foreach ($interaction->entitlements as $entitlement) {
                if ($entitlementPart = $this->discord->application->entitlements->get('id', $entitlement->id)) {
                    $entitlementPart->fill((array) $entitlement);
                } else {
                    $this->discord->application->entitlements->set($entitlement->id, $this->discord->application->entitlements->create($entitlement, true));
                }
            }
        }

        if ($interaction instanceof ApplicationCommand || $interaction instanceof ApplicationCommandAutocomplete) {
            /** @var ApplicationCommandData $command */
            $command = $interaction->data;
            if (isset($this->discord->application_commands[$command->name])) {
                $interaction instanceof ApplicationCommand
                    ? $this->discord->application_commands[$command->name]->execute($command->options ?? [], $interaction)
                    : $this->checkCommand($this->discord->application_commands[$command->name], $command->options, $interaction);
            }
        }

        return $interaction;
    }

    /**
     * Recursively checks and handles command options for an interaction.
     *
     * @param RegisteredCommand                                 $command     The command or subcommand to check.
     * @param ExCollectionInterface|RequestOption[]|null        $options     The list of options to process.
     * @param ApplicationCommand|ApplicationCommandAutocomplete $interaction The interaction instance from Discord.
     *
     * @return bool Returns true if a suggestion was triggered, otherwise false.
     */
    protected function checkCommand(RegisteredCommand $command, $options, Interaction $interaction): bool
    {
        foreach ($options as $option) {
            /** @var ?RegisteredCommand $subCommand */
            if ($subCommand = $command->getSubCommand($option->name)) {
                if ($option->focused) {
                    return $subCommand->suggest($interaction);
                }
                if (! empty($option->options)) {
                    return $this->checkCommand($subCommand, $option->options, $interaction);
                }
            } elseif ($option->focused) {
                return $command->suggest($interaction);
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function cacheMember(MemberRepository $members, array $memberdata): void
    {
        // Do not load members from cache as it may delay interaction codes.
        $id = $memberdata['user']->id ?? $memberdata['id'] ?? null;
        if ($id && $member = $members->offsetGet($id)) {
            $member->fill(['user' => $memberdata]);
        } else {
            $members->pushItem($members->create($memberdata, true));
        }
    }
}
