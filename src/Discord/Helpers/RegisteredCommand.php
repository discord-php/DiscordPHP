<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\Interactions\Request\Option;

/**
 * RegisteredCommand represents a command that has been registered with the
 * Discord servers and has a handler to handle when the command is triggered.
 *
 * https://discord.com/developers/docs/interactions/application-commands
 *
 * @since 7.0.0
 *
 * @author David Cole <david.cole1340@gmail.com>
 */
class RegisteredCommand
{
    /**
     * The Discord client.
     *
     * @var Discord Client.
     */
    protected $discord;

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
     * The callback to be called when the auto complete is triggered.
     *
     * @var callable
     */
    private $autocomplete_callback;

    /**
     * Array of sub-commands.
     *
     * @var RegisteredCommand[]
     */
    private $subCommands;

    /**
     * RegisteredCommand represents a command that has been registered with the
     * Discord servers and has a handler to handle when the command is triggered.
     *
     * @param Discord  $discord
     * @param string   $name
     * @param callable $callback
     */
    public function __construct(Discord $discord, string $name, callable $callback = null, ?callable $autocomplete_callback = null)
    {
        $this->discord = $discord;
        $this->name = $name;
        $this->callback = $callback;
        $this->autocomplete_callback = $autocomplete_callback;
    }

    /**
     * Executes the command. Will search for a sub-command if given, otherwise
     * executes the callback, if given.
     *
     * @param array       $options
     * @param Interaction $interaction
     *
     * @return bool Whether the command successfully executed.
     */
    public function execute(array $options, Interaction $interaction): bool
    {
        $params = Collection::for(Option::class, 'name');

        foreach ($options as $option) {
            if (isset($this->subCommands[$option->name])) {
                if ($this->subCommands[$option->name]->execute($option->options ?? [], $interaction)) {
                    return true;
                }
            }
            $params->pushItem($this->discord->getFactory()->part(Option::class, (array) $option, true));
        }

        if (isset($this->callback)) {
            ($this->callback)($interaction, $params);

            return true;
        }

        return false;
    }

    /**
     * Executes the command. Will search for a sub-command if given, otherwise
     * executes the callback, if given.
     *
     * @param Interaction $interaction
     *
     * @return bool Whether the command successfully executed.
     */
    public function suggest(Interaction $interaction): bool
    {
        if (is_callable($this->autocomplete_callback)) {
            $choice = ($this->autocomplete_callback)($interaction);
            if (is_array($choice)) {
                $interaction->autoCompleteResult($choice);
            }

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
     * Sets the callback for the auto complete suggestion.
     *
     * @param callable $callback
     */
    public function setAutoCompleteCallback(callable $autocomplete_callback)
    {
        $this->autocomplete_callback = $autocomplete_callback;
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
     * @param string|array  $name
     * @param callable      $callback
     * @param callable|null $autocomplete_callback
     *
     * @throws \LogicException
     *
     * @return static
     */
    public function addSubCommand($name, callable $callback = null, ?callable $autocomplete_callback = null): RegisteredCommand
    {
        if (is_array($name) && count($name) == 1) {
            $name = array_shift($name);
        }

        if (! is_array($name) || count($name) == 1) {
            if (isset($this->subCommands[$name])) {
                throw new \LogicException("The command `{$name}` already exists.");
            }

            return $this->subCommands[$name] = new static($this->discord, $name, $callback, $autocomplete_callback);
        }

        $subCommand = array_shift($name);

        if (! isset($this->subCommands[$subCommand])) {
            $this->addSubCommand($subCommand);
        }

        return $this->subCommands[$subCommand]->addSubCommand($name, $callback, $autocomplete_callback);
    }

    /**
     * Get command name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get sub commands.
     *
     * @return RegisteredCommand[]|null
     */
    public function getSubCommands()
    {
        return $this->subCommands;
    }
}
