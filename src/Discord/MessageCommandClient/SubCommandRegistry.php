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
 * Registry for managing sub-commands and their aliases.
 */
class SubCommandRegistry
{
    /**
     * @var array<string, Command>
     */
    protected array $subCommands = [];

    /**
     * @var array<string, string>
     */
    protected array $subCommandAliases = [];

    /**
     * Callable normalizer for command/alias names.
     *
     * @var callable
     */
    protected $normalizer;

    public function __construct(callable $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    protected function normalize(string $name): string
    {
        $fn = $this->normalizer;

        return $fn($name);
    }

    public function get(?string $command, bool $aliases = true): ?Command
    {
        if ($command !== null) {
            $command = $this->normalize($command);
        }

        if ($command !== null && array_key_exists($command, $this->subCommands)) {
            return $this->subCommands[$command];
        }

        if ($aliases && $command !== null) {
            if (array_key_exists($command, $this->subCommandAliases)) {
                $target = $this->subCommandAliases[$command];

                if (array_key_exists($target, $this->subCommands)) {
                    return $this->subCommands[$target];
                }
            }
        }

        return null;
    }

    /**
     * Register a Command instance and its aliases. Returns the normalized key.
     *
     * @param Command $commandInstance
     * @param array   $aliases
     *
     * @return string
     */
    public function register(Command $commandInstance, array $aliases = []): string
    {
        $key = $this->normalize($commandInstance->command);

        if (array_key_exists($key, $this->subCommandAliases)) {
            throw new \RuntimeException('A sub-command with the same name already exists as an alias for another sub-command.');
        }
        if (array_key_exists($key, $this->subCommands)) {
            throw new \RuntimeException('A sub-command with the same name already exists.');
        }

        $this->subCommands[$key] = $commandInstance;

        foreach ($aliases as $alias) {
            $this->registerAlias($alias, $key);
        }

        return $key;
    }

    public function unregister(string $command): void
    {
        $command = $this->normalize($command);

        if (! array_key_exists($command, $this->subCommands)) {
            throw new \RuntimeException('The sub-command does not exist.');
        }

        unset($this->subCommands[$command]);

        // Remove any aliases that pointed to this command.
        foreach ($this->subCommandAliases as $alias => $target) {
            if ($target === $command) {
                unset($this->subCommandAliases[$alias]);
            }
        }
    }

    public function registerAlias(string $alias, string $command): void
    {
        $alias = $this->normalize($alias);
        $command = $this->normalize($command);

        if (! array_key_exists($command, $this->subCommands)) {
            throw new \RuntimeException('The target sub-command does not exist.');
        }

        if (array_key_exists($alias, $this->subCommands)) {
            throw new \RuntimeException('Cannot create alias because a sub-command with that name already exists.');
        }

        if (array_key_exists($alias, $this->subCommandAliases)) {
            $existing = $this->subCommandAliases[$alias];
            if ($existing === $command) {
                return;
            }

            throw new \RuntimeException('Cannot remap alias because it is already mapped to a different sub-command.');
        }

        $this->subCommandAliases[$alias] = $command;
    }

    public function unregisterAlias(string $alias): void
    {
        $alias = $this->normalize($alias);

        if (! array_key_exists($alias, $this->subCommandAliases)) {
            throw new \RuntimeException('The sub-command alias does not exist.');
        }

        unset($this->subCommandAliases[$alias]);
    }

    /**
     * Returns all registered sub-commands.
     *
     * @return array<string, Command>
     */
    public function all(): array
    {
        return $this->subCommands;
    }
}
