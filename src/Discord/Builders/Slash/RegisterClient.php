<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Builders\Slash;

use Discord\Discord;
use Discord\Discord\Enums\ApplicationCommandOptionType;
use Discord\Parts\Interactions\Command\Command;
use Discord\Repository\Guild\CommandRepository;
use Discord\Repository\Interaction\GlobalCommandRepository;
use ReflectionClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @todo Fix compatibility
 * This class is used to register commands with Discord.
 * You should only need to use this class once, from thereon you can use the Client
 * class to listen for slash command requests.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class RegisterClient
{
    /**
     * Discord client.
     *
     * @var Discord
     */
    private $discord;

    /**
     * HTTP client constructor.
     *
     * @param Discord $discord Pass discord client instead of token
     */
    public function __construct(Discord $discord)
    {
        $this->discord = &$discord;
    }

    /**
     * Returns a list of commands.
     * @deprecated 7.0.0 Backported from DiscordPHP-Slash (now asynchrounous)
     *
     * @param string|null $guild_id The guild ID to get commands for.
     *
     * @return GlobalCommandRepository|CommandRepository
     */
    public function getCommands(?string $guild_id = null)
    {
        if ($guild_id) {
            $guild = $this->discord->guilds->get('id', $guild_id);
            return $guild->commands;
        } else {
            return $this->discord->commands;
        }
    }

    /**
     * Tries to get a command.
     * @deprecated 7.0.0 Backported from DiscordPHP-Slash (now asynchrounous)
     *
     * @param string $command_id
     * @param string $guild_id
     *
     * @return Command|ExtendedPromiseInterface A command if exists in the cache, a promise otherwise
     */
    public function getCommand(string $command_id, ?string $guild_id = null)
    {
        $guild = $this->discord->guilds->get('id', $guild_id);
        if ($command = $guild->commands->get('id', $command_id)) {
            return $command;
        }
        return $guild->commands->fetch($command_id);
    }

    /**
     * Creates a global command.
     * @deprecated 7.0.0 Backported from DiscordPHP-Slash (no more synchrounous)
     *
     * @param string $name
     * @param string $description
     * @param array  $options
     *
     * @return Command
     */
    public function createGlobalCommand(string $name, string $description, array $options = [])
    {
        foreach ($options as $key => $option) {
            $options[$key] = $this->resolveApplicationCommandOption($option);
        }

        $globalCommand = $this->discord->commands->create([
            'name' => $name,
            'description' => $description,
            'options' => $options,
        ]);

        $this->discord->commands->save($globalCommand);
        return $globalCommand;
    }

    /**
     * Creates a guild-specific command.
     * @deprecated 7.0.0 Backported from DiscordPHP-Slash (no more synchrounous)
     *
     * @param string $guild_id
     * @param string $name
     * @param string $description
     * @param array  $options
     *
     * @return Command
     */
    public function createGuildSpecificCommand(string $guild_id, string $name, string $description, array $options = [])
    {
        $guild = $this->discord->guilds->get('id', $guild_id);

        foreach ($options as $key => $option) {
            $options[$key] = $this->resolveApplicationCommandOption($option);
        }

        $guildCommand = $guild->commands->create([
            'name' => $name,
            'description' => $description,
            'options' => $options,
        ]);

        $guild->commands->save($guildCommand);
        return $guildCommand;
    }

    /**
     * Updates the Discord servers with the changes done to the given command.
     * @deprecated 7.0.0 Backported from DiscordPHP-Slash (no more synchrounous)
     *
     * @param Command $command
     *
     * @return Command
     */
    public function updateCommand(Command $command)
    {
        $raw = $command->getRawAttributes();

        foreach ($raw['options'] ?? [] as $key => $option) {
            $raw['options'][$key] = $this->resolveApplicationCommandOption($option);
        }

        if ($command->guild_id) {
            $guild = $this->discord->guilds->get('id', $command->guild_id);
            $guild->commands->save($command);
        } else {
            $this->discord->commands->save($command);
        }
        return $command;
    }

    /**
     * Deletes a command from the Discord servers. (no more synchrounous)
     *
     * @param Command $command
     */
    public function deleteCommand(Command $command)
    {
        if ($command->guild_id) {
            $guild = $this->discord->guilds->get('id', $command->guild_id);
            $guild->commands->delete($command);
        }

        $this->discord->commands->delete($command);
    }

    /**
     * Resolves an `ApplicationCommandOption` part.
     *
     * @param array $options
     *
     * @return array
     */
    private function resolveApplicationCommandOption(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
        ->setDefined([
            'type',
            'name',
            'description',
            'default',
            'required',
            'choices',
            'options',
        ])
        ->setAllowedTypes('type', 'int')
        ->setAllowedValues('type', array_values((new ReflectionClass(ApplicationCommandOptionType::class))->getConstants()))
        ->setAllowedTypes('name', 'string')
        ->setAllowedTypes('description', 'string')
        ->setAllowedTypes('default', 'bool')
        ->setAllowedTypes('required', 'bool')
        ->setAllowedTypes('choices', 'array')
        ->setAllowedTypes('options', 'array');

        $options = $resolver->resolve($options);

        foreach ($options['choices'] ?? [] as $key => $choice) {
            $options['choices'][$key] = $this->resolveApplicationCommandOptionChoice($choice);
        }

        foreach ($options['options'] ?? [] as $key => $option) {
            $options['options'][$key] = $this->resolveApplicationCommandOption($option);
        }

        return $options;
    }

    /**
     * Resolves an `ApplicationCommandOption` part.
     *
     * @param array $options
     *
     * @return array
     */
    private function resolveApplicationCommandOptionChoice(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
        ->setDefined([
            'name',
            'value',
        ])
        ->setAllowedTypes('name', 'string')
        ->setAllowedTypes('value', ['string', 'int']);

        return $resolver->resolve($options);
    }
}
