<?php

/*
 * This file was a part of the DiscordPHP-Slash project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license which is
 * bundled with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions\Command;

use Discord\Parts\Interactions\Interaction;
use InvalidArgumentException;

/**
 * RegisteredCommand represents a command that has been registered
 * with the Discord servers and has a handler to handle when the
 * command is triggered.
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class RegisteredCommand
{
    /**
     * The name of the command.
     *
     * @var string
     */
    private $name;

    /**
     * The callback to be called when the command is triggered.
     *
     * @var callable
     */
    private $callback;

    /**
     * Array of sub-commands.
     *
     * @var RegisteredCommand[]
     */
    private $subCommands;

    /**
     * RegisteredCommand represents a command that has been registered
     * with the Discord servers and has a handler to handle when the
     * command is triggered.
     *
     * @param string   $name
     * @param callable $callback
     */
    public function __construct(string $name, callable $callback = null)
    {
        $this->name = $name;
        $this->callback = $callback;
    }

    /**
     * Executes the command. Will search for a sub-command if given,
     * otherwise executes the callback, if given.
     *
     * @param array       $options
     * @param Interaction $interaction
     *
     * @return bool Whether the command successfully executed.
     */
    public function execute(array $options, Interaction $interaction): bool
    {
        foreach ($options as $option) {
            if (isset($this->subCommands[$option['name']])) {
                if ($this->subCommands[$option['name']]->execute($option['options'] ?? [], $interaction)) {
                    return true;
                }
            }
        }

        if (! is_null($this->callback)) {
            ($this->callback)($interaction, new Choices($options));

            return true;
        }

        return false;
    }

    /**
     * Sets the callback for the command.
     *
     * @param callable $callback
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Tries to get a sub-command if exists.
     *
     * @param string $name
     *
     * @return RegisteredCommand|null
     */
    public function getSubCommand(string $name): ?RegisteredCommand
    {
        return $this->subCommands[$name] ?? null;
    }

    /**
     * Adds a sub-command to the command.
     *
     * @param string|array $name
     * @param callable     $callback
     *
     * @return RegisteredCommand
     */
    public function addSubCommand($name, callable $callback = null): RegisteredCommand
    {
        if (is_array($name) && count($name) == 1) {
            $name = array_shift($name);
        }

        if (! is_array($name) || count($name) == 1) {
            if (isset($this->subCommands[$name])) {
                throw new InvalidArgumentException("The command `{$name}` already exists.");
            }

            return $this->subCommands[$name] = new static($name, $callback);
        }

        $baseCommand = array_shift($name);

        if (! isset($this->subCommands[$baseCommand])) {
            $this->addSubCommand($baseCommand);
        }

        return $this->subCommands[$baseCommand]->addSubCommand($name, $callback);
    }
}
