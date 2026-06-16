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

/**
 * Registry for message command client commands and aliases.
 *
 * @since 10.49.0
 */
final class CommandRegistry
{
    /**
     * Map of command name => Command instance.
     *
     * @var array<string, Command>
     */
    protected array $commands = [];

    /**
     * Map of alias => command name.
     *
     * @var array<string, string>
     */
    protected array $aliases = [];

    /**
     * Whether command names/aliases should be treated case-insensitively.
     *
     * @var bool
     */
    protected bool $caseInsensitive;

    /**
     * Creates a command registry instance.
     *
     * @param bool $caseInsensitive Whether command names/aliases should be treated case-insensitively.
     */
    public function __construct(bool $caseInsensitive = false)
    {
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * Normalize a command/alias name according to the case sensitivity setting.
     */
    protected function normalize(string $name): string
    {
        return $this->caseInsensitive
            ? (function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name))
            : $name;
    }

    /**
     * Add a command to the registry.
     *
     * @param string  $name    Command trigger name.
     * @param Command $command Command instance.
     *
     * @throws \RuntimeException When a command with the same name already exists.
     */
    public function add(string $name, Command $command): void
    {
        $name = $this->normalize($name);
        if (isset($this->commands[$name])) {
            throw new \RuntimeException("A command with the name {$name} already exists.");
        }

        $this->commands[$name] = $command;
    }

    /**
     * Remove a command from the registry.
     * 
     * This does not remove any aliases pointing to this command, so they will become invalid until removed or updated.
     *
     * @param string $name Command trigger name.
     */
    public function remove(string $name): void
    {
        $name = $this->normalize($name);
        unset($this->commands[$name]);
    }

    /**
     * Check whether a command or alias exists.
     *
     * @param string $name Command or alias to check.
     */
    public function has(string $name): bool
    {
        $name = $this->normalize($name);

        return isset($this->commands[$name]) || isset($this->aliases[$name]);
    }

    /**
     * Retrieve a command by name or alias.
     *
     * @param string $name Command name or alias.
     *
     * @return Command|null The command instance or null if not found.
     */
    public function get(string $name): ?Command
    {
        $name = $this->normalize($name);
        if (isset($this->commands[$name])) {
            return $this->commands[$name];
        }

        if (isset($this->aliases[$name]) && isset($this->commands[$this->aliases[$name]])) {
            return $this->commands[$this->aliases[$name]];
        }

        return null;
    }

    /**
     * Add an alias for an existing command name.
     *
     * @param string $alias       Alias name.
     * @param string $commandName Target command name.
     */
    public function addAlias(string $alias, string $commandName): void
    {
        $alias = $this->normalize($alias);
        $commandName = $this->normalize($commandName);
        $this->aliases[$alias] = $commandName;
    }

    /**
     * Remove an alias.
     *
     * @param string $alias Alias to remove.
     */
    public function removeAlias(string $alias): void
    {
        $alias = $this->normalize($alias);
        unset($this->aliases[$alias]);
    }

    /**
     * Return all registered commands.
     *
     * @return array<string, Command>
     */
    public function all(): array
    {
        return $this->commands;
    }

    /**
     * Return all registered aliases.
     *
     * @return array<string, string>
     */
    public function aliases(): array
    {
        return $this->aliases;
    }
}
