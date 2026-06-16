<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\MessageCommandClient;

use Discord\MessageCommandClient;
use Discord\Parts\Channel\Message;

/**
 * A message-based command that the MessageCommandClient will listen for.
 *
 * @since 10.49.0
 */
class Command
{
    /**
     * The trigger for the command.
     *
     * @var string
     */
    public string $command;

    /**
     * Short description shown in help listings.
     *
     * @var string
     */
    public string $description;

    /**
     * Long description for detailed help.
     *
     * @var string
     */
    public string $longDescription;

    /**
     * Usage string.
     *
     * @var string
     */
    public string $usage;

    /**
     * Cooldown duration in milliseconds.
     *
     * @var int
     */
    public int $cooldown;

    /**
     * Message displayed when the cooldown is active.
     *
     * @var string
     */
    public string $cooldownMessage;

    /**
     * Whether this command should be shown in help listings.
     *
     * @var bool
     */
    public bool $showHelp;

    /**
     * Cooldowns map: user ID => timestamp when cooldown expires.
     *
     * @var array<string,int>
     */
    protected array $cooldowns = [];

    /**
     * Sub-commands map: name => Command.
     *
     * @var array<string, Command>
     */
    protected array $subCommands = [];

    /**
     * Sub-command aliases: alias => sub-command name.
     *
     * @var array<string, string>
     */
    protected array $subCommandAliases = [];

    /**
     * Owning MessageCommandClient instance.
     *
     * @var MessageCommandClient
     */
    protected MessageCommandClient $client;

    /**
     * The callable executed when the command is invoked.
     *
     * @var callable
     */
    protected $callable;

    /**
     * Creates a command instance.
     *
     * @param MessageCommandClient $client          The message command client.
     * @param string               $command         Command trigger.
     * @param callable             $callable        Callable to execute.
     * @param string               $description     Short description.
     * @param string               $longDescription Long description.
     * @param string               $usage           Usage string.
     * @param int                  $cooldown        Cooldown in milliseconds.
     * @param string               $cooldownMessage Cooldown message.
     * @param bool                 $showHelp        Whether to show in help.
     */
    public function __construct(
        MessageCommandClient $client,
        string $command,
        callable $callable,
        string $description,
        string $longDescription,
        string $usage,
        int $cooldown,
        string $cooldownMessage,
        bool $showHelp = true
    ) {
        $this->client = $client;
        $this->command = $command;
        $this->callable = $callable;
        $this->description = $description;
        $this->longDescription = $longDescription;
        $this->usage = $usage;
        $this->cooldown = $cooldown;
        $this->cooldownMessage = $cooldownMessage;
        $this->showHelp = $showHelp;
    }

    /**
     * Attempts to get a sub command.
     *
     * @param string $command The sub-command to get.
     * @param bool   $aliases Whether to search aliases as well.
     *
     * @return Command|null
     */
    public function getCommand(string $command, bool $aliases = true): ?Command
    {
        if (array_key_exists($command, $this->subCommands)) {
            return $this->subCommands[$command];
        }

        if ($aliases && array_key_exists($command, $this->subCommandAliases)) {
            return $this->subCommands[$this->subCommandAliases[$command]];
        }

        return null;
    }

    /**
     * Registers a new sub-command.
     *
     * @param string          $command  The command name.
     * @param callable|string $callable The function called when the sub-command is executed.
     * @param array           $options  Options for the sub-command.
     *
     * @return Command The created sub-command instance.
     */
    public function registerSubCommand(string $command, $callable, array $options = []): Command
    {
        if (array_key_exists($command, $this->subCommands)) {
            throw new \RuntimeException("A sub-command with the name {$command} already exists.");
        }

        if ($command !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $command = function_exists('mb_strtolower')
                ? mb_strtolower($command)
                : strtolower($command);
        }

        ['command' => $commandInstance, 'options' => $resolvedOptions] = $this->client->buildCommand($command, $callable, $options);
        $this->subCommands[$command] = $commandInstance;

        foreach ($resolvedOptions['aliases'] as $alias) {
            $this->registerSubCommandAlias($alias, $command);
        }

        return $commandInstance;
    }

    /**
     * Unregister a sub-command.
     *
     * @param string $command Sub-command name.
     */
    public function unregisterSubCommand(string $command): void
    {
        if (! array_key_exists($command, $this->subCommands)) {
            throw new \RuntimeException("A sub-command with the name {$command} does not exist.");
        }
        if ($command !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $command = function_exists('mb_strtolower')
                ? mb_strtolower($command)
                : strtolower($command);
        }
        unset($this->subCommands[$command]);
    }

    /**
     * Register an alias for a sub-command.
     *
     * @param string $alias   Alias name.
     * @param string $command Target sub-command name.
     */
    public function registerSubCommandAlias(string $alias, string $command): void
    {
        if ($alias !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $alias = function_exists('mb_strtolower')
                ? mb_strtolower($alias)
                : strtolower($alias);
        }

        $this->subCommandAliases[$alias] = $command;
    }

    /**
     * Unregister a sub-command alias.
     *
     * @param string $alias Alias name.
     */
    public function unregisterSubCommandAlias(string $alias): void
    {
        if (! array_key_exists($alias, $this->subCommandAliases)) {
            throw new \RuntimeException("A sub-command alias with the name {$alias} does not exist.");
        }

        unset($this->subCommandAliases[$alias]);
    }

    /**
     * Executes the command.
     *
     * @param Message $message The message that triggered the command.
     * @param array   $args    Parsed arguments.
     *
     * @return mixed The response from the callable or sub-command.
     */
    public function handle(Message $message, array $args)
    {
        $subCommand = $originalSubCommand = array_shift($args);

        if ($subCommand !== null && $this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            $subCommand = function_exists('mb_strtolower')
                ? mb_strtolower($subCommand)
                : strtolower($subCommand);
        }

        if (array_key_exists($subCommand, $this->subCommands)) {
            return $this->subCommands[$subCommand]->handle($message, $args);
        } elseif (array_key_exists($subCommand, $this->subCommandAliases)) {
            return $this->subCommands[$this->subCommandAliases[$subCommand]]->handle($message, $args);
        }

        if (null !== $subCommand) {
            array_unshift($args, $originalSubCommand);
        }

        $currentTime = (int) round(microtime(true) * 1000);

        if ($cooldownResult = $this->enforceCooldown($message, $currentTime)) {
            return $cooldownResult;
        }

        return call_user_func_array($this->callable, [$message, $args]);
    }

    /**
     * Enforce and update cooldowns for a message author.
     *
     * @param Message $message     The message that triggered the command.
     * @param int     $currentTime Current time in milliseconds.
     *
     * @return string|null Returns a formatted cooldown message when the author is on cooldown, or null when allowed.
     */
    protected function enforceCooldown(Message $message, int $currentTime): ?string
    {
        if ($this->cooldown <= 0) {
            return null;
        }

        $userId = $message->author->id;

        if (isset($this->cooldowns[$userId])) {
            if ($this->cooldowns[$userId] < $currentTime) {
                $this->cooldowns[$userId] = $currentTime + $this->cooldown;

                return null;
            }

            return sprintf($this->cooldownMessage, (($this->cooldowns[$userId] - $currentTime) / 1000));
        }

        $this->cooldowns[$userId] = $currentTime + $this->cooldown;

        return null;
    }

    /**
     * Gets help information for this command.
     *
     * @param string $prefix The prefix to display before the command.
     *
     * @return array Help information.
     */
    public function getHelp(string $prefix): array
    {
        if (! $this->showHelp) {
            return [];
        }

        $subCommandsHelp = [];

        foreach ($this->subCommands as $command) {
            if ($command->showHelp) {
                $subCommandsHelp[] = $command->getHelp($prefix.$this->command.' ');
            }
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
     * Handles dynamic getters for some public properties.
     *
     * @param string $variable Property name.
     *
     * @return mixed|null
     */
    public function __get(string $variable)
    {
        static $allowed = ['command', 'description', 'longDescription', 'usage', 'cooldown', 'cooldownMessage', 'showHelp'];

        if (in_array($variable, $allowed, true)) {
            return $this->{$variable};
        }

        return null;
    }

    /**
     * Magic invoke to allow the command to be called like a callable.
     *
     * @param Message $message The message that triggered the command.
     * @param array   $args    Parsed arguments.
     *
     * @return mixed The result of {@see handle()}.
     */
    public function __invoke(Message $message, array $args)
    {
        return $this->handle($message, $args);
    }
}
