<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Discord\CommandClient\Command;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Provides an easy way to have triggerable commands.
 */
class DiscordCommandClient extends Discord
{
    /**
     * An array of options passed to the client.
     *
     * @var array Options.
     */
    protected $commandClientOptions;

    /**
     * A map of the commands.
     *
     * @var array Commands.
     */
    protected $commands = [];

    /**
     * A map of aliases for commands.
     *
     * @var array Aliases.
     */
    protected $aliases = [];

    /**
     * Constructs a new command client.
     *
     * @param array $options An array of options.
     */
    public function __construct(array $options = [])
    {
        $this->commandClientOptions = $this->resolveCommandClientOptions($options);

        $discordOptions = array_merge($this->commandClientOptions['discordOptions'], [
            'token' => $this->commandClientOptions['token'],
        ]);

        parent::__construct($discordOptions);

        $this->on('ready', function () {
            $this->commandClientOptions['prefix'] = str_replace('@mention', (string) $this->user, $this->commandClientOptions['prefix']);
            $this->commandClientOptions['name'] = str_replace('<UsernamePlaceholder>', $this->username, $this->commandClientOptions['name']);

            $this->on('message', function ($message) {
                if ($message->author->id == $this->id) {
                    return;
                }

                if (substr($message->content, 0, strlen($this->commandClientOptions['prefix'])) == $this->commandClientOptions['prefix']) {
                    $withoutPrefix = substr($message->content, strlen($this->commandClientOptions['prefix']));
                    $args = str_getcsv($withoutPrefix, ' ');
                    $command = array_shift($args);

                    if (array_key_exists($command, $this->commands)) {
                        $command = $this->commands[$command];
                    } elseif (array_key_exists($command, $this->aliases)) {
                        $command = $this->commands[$this->aliases[$command]];
                    } else {
                        // Command doesn't exist.
                        return;
                    }

                    $result = $command->handle($message, $args);

                    if (is_string($result)) {
                        $message->reply($result);
                    }
                }
            });
        });

        if ($this->commandClientOptions['defaultHelpCommand']) {
            $this->registerCommand('help', function ($message, $args) {
                $prefix = str_replace((string) $this->user, '@'.$this->username, $this->commandClientOptions['prefix']);

                if (count($args) > 0) {
                    $commandString = implode(' ', $args);
                    $command = $this->getCommand($commandString);

                    if (is_null($command)) {
                        return "The command {$commandString} does not exist.";
                    }

                    $help = $command->getHelp($prefix);
                    $response = "```\r\n{$this->commandClientOptions['name']} - {$this->commandClientOptions['description']}\r\n\r\n{$help['text']}Aliases:\r\n";

                    foreach ($this->aliases as $alias => $command) {
                        if ($command != $commandString) {
                            continue;
                        }

                        $response .= "- {$alias}\r\n";
                    }

                    $response .= '```';

                    $message->channel->sendMessage($response);

                    return;
                }

                $response = "```\r\n{$this->commandClientOptions['name']} - {$this->commandClientOptions['description']}\r\n\r\n";

                foreach ($this->commands as $command) {
                    $help = $command->getHelp($prefix);
                    $response .= $help['text'];
                }

                $response .= "Run {$prefix}help command to get more information about a specific function.\r\n";
                $response .= '```';

                $message->channel->sendMessage($response);
            }, [
                'description' => 'Provides a list of commands available.',
                'usage'       => '[command]',
            ]);
        }
    }

    /**
     * Registers a new command.
     *
     * @param string           $command  The command name.
     * @param \Callable|string $callable The function called when the command is executed.
     * @param array            $options  An array of options.
     *
     * @return Command The command instance.
     */
    public function registerCommand($command, $callable, array $options = [])
    {
        if (array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name {$command} already exists.");
        }

        list($commandInstance, $options) = $this->buildCommand($command, $callable, $options);
        $this->commands[$command]        = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            $this->addCommandAlias($alias, $command);
        }

        return $commandInstance;
    }

    /**
     * Unregisters a command.
     *
     * @param string $command The command name.
     */
    public function unregisterCommand($command)
    {
        if (! array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name {$command} does not exist.");
        }

        unset($this->commands[$command]);
    }

    /**
     * Registers a command alias.
     *
     * @param string $alias   The alias to add.
     * @param string $command The command.
     */
    public function registerAlias($alias, $command)
    {
        $this->aliases[$alias] = $command;
    }

    /**
     * Unregisters a command alias.
     *
     * @param string $alias The alias name.
     */
    public function unregisterCommandAlias($alias)
    {
        if (! array_key_exists($alias, $this->aliases)) {
            throw new \Exception("A command alias with the name {$alias} does not exist.");
        }

        unset($this->aliases[$alias]);
    }

    /**
     * Attempts to get a command.
     *
     * @param string $command The command to get.
     * @param bool   $aliases Whether to search aliases as well.
     *
     * @return Command|null The command.
     */
    public function getCommand($command, $aliases = true)
    {
        if (array_key_exists($command, $this->commands)) {
            return $this->commands[$command];
        }

        if (array_key_exists($command, $this->aliases) && $aliases) {
            return $this->commands[$this->aliases[$command]];
        }
    }

    /**
     * Builds a command and returns it.
     *
     * @param string           $command  The command name.
     * @param \Callable|string $callable The function called when the command is executed.
     * @param array            $options  An array of options.
     *
     * @return array[Command, array] The command instance and options.
     */
    public function buildCommand($command, $callable, array $options = [])
    {
        if (is_string($callable)) {
            $callable = function ($message) use ($callable) {
                return $callable;
            };
        } elseif (is_array($callable) && ! is_callable($callable)) {
            $callable = function ($message) use ($callable) {
                return $callable[array_rand($callable)];
            };
        }

        if (! is_callable($callable)) {
            throw new \Exception('The callable parameter must be a string, array or callable.');
        }

        $options = $this->resolveCommandOptions($options);

        $commandInstance = new Command(
            $this, $command, $callable,
            $options['description'], $options['usage'], $options['aliases']);

        return [$commandInstance, $options];
    }

    /**
     * Resolves command options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandOptions(array $options)
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'description',
                'usage',
                'aliases',
            ])
            ->setDefaults([
                'description' => 'No description provided.',
                'usage'       => '',
                'aliases'     => [],
            ]);

        $options = $resolver->resolve($options);

        if (! empty($options['usage'])) {
            $options['usage'] .= ' ';
        }

        return $options;
    }

    /**
     * Resolves the options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandClientOptions(array $options)
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setRequired('token')
            ->setAllowedTypes('token', 'string')
            ->setDefined([
                'token',
                'prefix',
                'name',
                'description',
                'defaultHelpCommand',
                'discordOptions',
            ])
            ->setDefaults([
                'prefix'             => '@mention ',
                'name'               => '<UsernamePlaceholder>',
                'description'        => 'A bot made with DiscordPHP.',
                'defaultHelpCommand' => true,
                'discordOptions'     => [],
            ]);

        return $resolver->resolve($options);
    }
}
