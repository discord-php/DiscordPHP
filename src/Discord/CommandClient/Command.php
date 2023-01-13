<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\CommandClient;

use Discord\DiscordCommandClient;
use Discord\Parts\Channel\Message;

/**
 * A command that the Command Client will listen for.
 */
class Command
{
    /**
     * The trigger for the command.
     *
     * @var string Command trigger.
     */
    protected $command;

    /**
     * The short description of the command.
     *
     * @var string Description.
     */
    protected $description;

    /**
     * The long description of the command.
     *
     * @var string Long description.
     */
    protected $longDescription;

    /**
     * The usage of the command.
     *
     * @var string Command usage.
     */
    protected $usage;

    /**
     * The cooldown of the command in milliseconds.
     *
     * @var int Command cooldown.
     */
    protected $cooldown;

    /**
     * The cooldown message to show when a cooldown is in effect.
     *
     * @var string Command cooldown message.
     */
    protected $cooldownMessage;

    /**
     * An array of cooldowns for commands.
     *
     * @var array Cooldowns.
     */
    protected $cooldowns = [];

    /**
     * A map of sub-commands.
     *
     * @var array Sub-Commands.
     */
    protected $subCommands = [];

    /**
     * A map of sub-command aliases.
     *
     * @var array Sub-Command aliases.
     */
    protected $subCommandAliases = [];
    /**
     * @var DiscordCommandClient
     */
    protected $client;
    /**
     * @var callable
     */
    protected $callable;

    /**
     * Creates a command instance.
     *
     * @param DiscordCommandClient $client          The Discord Command Client.
     * @param string               $command         The command trigger.
     * @param callable             $callable        The callable function.
     * @param string               $description     The short description of the command.
     * @param string               $longDescription The long description of the command.
     * @param string               $usage           The usage of the command.
     * @param int                  $cooldown        The cooldown of the command in milliseconds.
     * @param string               $cooldownMessage The cooldown message to show when a cooldown is in effect.
     */
    public function __construct(
        DiscordCommandClient $client,
        string $command,
        callable $callable,
        string $description,
        string $longDescription,
        string $usage,
        int $cooldown,
        string $cooldownMessage
    ) {
        $this->client = $client;
        $this->command = $command;
        $this->callable = $callable;
        $this->description = $description;
        $this->longDescription = $longDescription;
        $this->usage = $usage;
        $this->cooldown = $cooldown;
        $this->cooldownMessage = $cooldownMessage;
    }

    /**
     * Attempts to get a sub command.
     *
     * @param string $command The command to get.
     * @param bool   $aliases WHether to search aliases as well.
     *
     * @return Command|null
     */
    public function getCommand(string $command, bool $aliases = true): ?Command
    {
        if (array_key_exists($command, $this->subCommands)) {
            return $this->subCommands[$command];
        }

        if (array_key_exists($command, $this->subCommandAliases) && $aliases) {
            return $this->subCommands[$this->subCommandAliases[$command]];
        }

        return null;
    }

    /**
     * Registers a new command.
     *
     * @param string          $command  The command name.
     * @param callable|string $callable The function called when the command is executed.
     * @param array           $options  An array of options.
     *
     * @return Command    The command instance.
     * @throws \Exception
     */
    public function registerSubCommand(string $command, $callable, array $options = []): Command
    {
        if (array_key_exists($command, $this->subCommands)) {
            throw new \Exception("A sub-command with the name {$command} already exists.");
        }

        if ($command !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $command = strtolower($command);
        }

        list($commandInstance, $options) = $this->client->buildCommand($command, $callable, $options);
        $this->subCommands[$command] = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            $this->registerSubCommandAlias($alias, $command);
        }

        return $commandInstance;
    }

    /**
     * Unregisters a sub-command.
     *
     * @param  string     $command The command name.
     * @throws \Exception
     */
    public function unregisterSubCommand(string $command): void
    {
        if (! array_key_exists($command, $this->subCommands)) {
            throw new \Exception("A sub-command with the name {$command} does not exist.");
        }

        unset($this->subCommands[$command]);
    }

    /**
     * Registers a sub-command alias.
     *
     * @param string $alias   The alias to add.
     * @param string $command The command.
     */
    public function registerSubCommandAlias(string $alias, string $command): void
    {
        if ($alias !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $alias = strtolower($alias);
        }

        $this->subCommandAliases[$alias] = $command;
    }

    /**
     * Unregisters a sub-command alias.
     *
     * @param  string     $alias The alias name.
     * @throws \Exception
     */
    public function unregisterSubCommandAlias(string $alias): void
    {
        if (! array_key_exists($alias, $this->subCommandAliases)) {
            throw new \Exception("A sub-command alias with the name {$alias} does not exist.");
        }

        unset($this->subCommandAliases[$alias]);
    }

    /**
     * Executes the command.
     *
     * @param Message $message The message.
     * @param array   $args    An array of arguments.
     *
     * @return mixed The response.
     */
    public function handle(Message $message, array $args)
    {
        $subCommand = $originalSubCommand = array_shift($args);

        if ($subCommand !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $subCommand = strtolower($subCommand);
        }

        if (array_key_exists($subCommand, $this->subCommands)) {
            return $this->subCommands[$subCommand]->handle($message, $args);
        } elseif (array_key_exists($subCommand, $this->subCommandAliases)) {
            return $this->subCommands[$this->subCommandAliases[$subCommand]]->handle($message, $args);
        }

        if (! is_null($subCommand)) {
            array_unshift($args, $originalSubCommand);
        }

        $currentTime = round(microtime(true) * 1000);
        if (isset($this->cooldowns[$message->author->id])) {
            if ($this->cooldowns[$message->author->id] < $currentTime) {
                $this->cooldowns[$message->author->id] = $currentTime + $this->cooldown;
            } else {
                return sprintf($this->cooldownMessage, (($this->cooldowns[$message->author->id] - $currentTime) / 1000));
            }
        } else {
            $this->cooldowns[$message->author->id] = $currentTime + $this->cooldown;
        }

        return call_user_func_array($this->callable, [$message, $args]);
    }

    /**
     * Gets help for the command.
     *
     * @param string $prefix The prefix of the bot.
     *
     * @return array The help.
     */
    public function getHelp(string $prefix): array
    {
        $subCommandsHelp = [];

        foreach ($this->subCommands as $command) {
            $subCommandsHelp[] = $command->getHelp($prefix.$this->command.' ');
        }

        return [
            'command' => $prefix.$this->command,
            'description' => $this->description,
            'longDescription' => $this->longDescription,
            'usage' => $this->usage,
            'subCommandsHelp' => $subCommandsHelp,
        ];
    }

    /**
     * Handles dynamic get calls to the class.
     *
     * @param string $variable The variable to get.
     *
     * @return string|int|false The value.
     */
    public function __get(string $variable)
    {
        $allowed = ['command', 'description', 'longDescription', 'usage', 'cooldown', 'cooldownMessage'];

        if (in_array($variable, $allowed)) {
            return $this->{$variable};
        }

        return false;
    }
}
