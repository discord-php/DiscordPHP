<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord;

use Discord\CommandClient\Command;
use Discord\Parts\Embed\Embed;
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
     * @param  array      $options An array of options.
     * @throws \Exception
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

            foreach ($this->commandClientOptions['prefixes'] as $key => $prefix) {
                if (contains($prefix, ['@mention'])) {
                    $this->commandClientOptions['prefixes'][] = str_replace('@mention', "<@{$this->user->id}>", $prefix);
                    $this->commandClientOptions['prefixes'][] = str_replace('@mention', "<@!{$this->user->id}>", $prefix);
                    unset($this->commandClientOptions['prefixes'][$key]);
                }
            }

            $this->on('message', function ($message) {
                if ($message->author->id == $this->id) {
                    return;
                }

                if ($withoutPrefix = $this->checkForPrefix($message->content)) {
                    $args = str_getcsv($withoutPrefix, ' ');
                    $command = array_shift($args);

                    if ($command !== null && $this->commandClientOptions['caseInsensitiveCommands']) {
                        $command = strtolower($command);
                    }

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
                $fullCommandString = implode(' ', $args);

                if (count($args) > 0) {
                    $command = $this;
                    while (count($args) > 0) {
                        $commandString = array_shift($args);
                        $newCommand = $command->getCommand($commandString);

                        if (is_null($newCommand)) {
                            return "The command {$commandString} does not exist.";
                        }

                        $command = $newCommand;
                    }

                    $help = $command->getHelp($prefix);

                    $embed = new Embed($this);
                    $embed->setAuthor($this->commandClientOptions['name'], $this->client->user->avatar)
                        ->setTitle($prefix.$fullCommandString.'\'s Help')
                        ->setDescription(! empty($help['longDescription']) ? $help['longDescription'] : $help['description'])
                        ->setFooter($this->commandClientOptions['name']);

                    if (! empty($help['usage'])) {
                        $embed->addFieldValues('Usage', '``'.$help['usage'].'``', true);
                    }

                    if (! empty($this->aliases)) {
                        $aliasesString = '';
                        foreach ($this->aliases as $alias => $command) {
                            if ($command != $commandString) {
                                continue;
                            }

                            $aliasesString .= "{$alias}\r\n";
                        }

                        if (! empty($aliasesString)) {
                            $embed->addFieldValues('Aliases', $aliasesString, true);
                        }
                    }

                    if (! empty($help['subCommandsHelp'])) {
                        foreach ($help['subCommandsHelp'] as $subCommandHelp) {
                            $embed->addFieldValues($subCommandHelp['command'], $subCommandHelp['description'], true);
                        }
                    }

                    $message->channel->sendEmbed($embed);

                    return;
                }

                $embed = new Embed($this);
                $embed->setAuthor($this->commandClientOptions['name'], $this->client->avatar)
                    ->setTitle($this->commandClientOptions['name'])
                    ->setType(Embed::TYPE_RICH)
                    ->setFooter($this->commandClientOptions['name']);

                $commandsDescription = '';
                $embedfields = [];
                foreach ($this->commands as $command) {
                    $help = $command->getHelp($prefix);
                    $embedfields[] = [
                        'name' => $help['command'],
                        'value' => $help['description'],
                        'inline' => true,
                    ];
                    $commandsDescription .= "\n\n`".$help['command']."`\n".$help['description'];

                    foreach ($help['subCommandsHelp'] as $subCommandHelp) {
                        $embedfields[] = [
                            'name' => $subCommandHelp['command'],
                            'value' => $subCommandHelp['description'],
                            'inline' => true,
                        ];
                        $commandsDescription .= "\n\n`".$subCommandHelp['command']."`\n".$subCommandHelp['description'];
                    }
                }
                // Use embed fields in case commands count is below limit
                if (count($embedfields) <= 25) {
                    foreach ($embedfields as $field) {
                        $embed->addField($field);
                    }
                    $commandsDescription = '';
                }

                $embed->setDescription(substr($this->commandClientOptions['description'].$commandsDescription, 0, 2048));

                $message->channel->sendEmbed($embed);
            }, [
                'description' => 'Provides a list of commands available.',
                'usage' => '[command]',
            ]);
        }
    }

    /**
     * Checks for a prefix in the message content, and returns the content
     * of the message minus the prefix if a prefix was detected. If no prefix
     * is detected, null is returned.
     *
     * @param string $content
     *
     * @return string|null
     */
    protected function checkForPrefix(string $content): ?string
    {
        foreach ($this->commandClientOptions['prefixes'] as $prefix) {
            if (substr($content, 0, strlen($prefix)) == $prefix) {
                return substr($content, strlen($prefix));
            }
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
    public function registerCommand(string $command, $callable, array $options = []): Command
    {
        if ($command !== null && $this->commandClientOptions['caseInsensitiveCommands']) {
            $command = strtolower($command);
        }
        if (array_key_exists($command, $this->commands)) {
            throw new \Exception("A command with the name {$command} already exists.");
        }

        list($commandInstance, $options) = $this->buildCommand($command, $callable, $options);
        $this->commands[$command] = $commandInstance;

        foreach ($options['aliases'] as $alias) {
            if ($alias !== null && $this->commandClientOptions['caseInsensitiveCommands']) {
                $alias = strtolower($alias);
            }
            $this->registerAlias($alias, $command);
        }

        return $commandInstance;
    }

    /**
     * Unregisters a command.
     *
     * @param  string     $command The command name.
     * @throws \Exception
     */
    public function unregisterCommand(string $command): void
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
    public function registerAlias(string $alias, string $command): void
    {
        $this->aliases[$alias] = $command;
    }

    /**
     * Unregisters a command alias.
     *
     * @param  string     $alias The alias name.
     * @throws \Exception
     */
    public function unregisterCommandAlias(string $alias): void
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
    public function getCommand(string $command, bool $aliases = true): ?Command
    {
        if (array_key_exists($command, $this->commands)) {
            return $this->commands[$command];
        }

        if (array_key_exists($command, $this->aliases) && $aliases) {
            return $this->commands[$this->aliases[$command]];
        }

        return null;
    }

    /**
     * Builds a command and returns it.
     *
     * @param string          $command  The command name.
     * @param callable|string $callable The function called when the command is executed.
     * @param array           $options  An array of options.
     *
     * @return Command[]|array[] The command instance and options.
     */
    public function buildCommand(string $command, $callable, array $options = []): array
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
            $this,
            $command,
            $callable,
            $options['description'],
            $options['longDescription'],
            $options['usage'],
            $options['cooldown'],
            $options['cooldownMessage']
        );

        return [$commandInstance, $options];
    }

    /**
     * Resolves command options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setDefined([
                'description',
                'longDescription',
                'usage',
                'aliases',
                'cooldown',
                'cooldownMessage',
            ])
            ->setDefaults([
                'description' => 'No description provided.',
                'longDescription' => '',
                'usage' => '',
                'aliases' => [],
                'cooldown' => 0,
                'cooldownMessage' => 'please wait %d second(s) to use this command again.',
            ]);

        $options = $resolver->resolve($options);

        return $options;
    }

    /**
     * Resolves the options.
     *
     * @param array $options Array of options.
     *
     * @return array Options.
     */
    protected function resolveCommandClientOptions(array $options): array
    {
        $resolver = new OptionsResolver();

        $resolver
            ->setRequired('token')
            ->setAllowedTypes('token', 'string')
            ->setDefined([
                'token',
                'prefix',
                'prefixes',
                'name',
                'description',
                'defaultHelpCommand',
                'discordOptions',
                'caseInsensitiveCommands',
            ])
            ->setDefaults([
                'prefix' => '@mention ',
                'prefixes' => [],
                'name' => '<UsernamePlaceholder>',
                'description' => 'A bot made with DiscordPHP '.self::VERSION.'.',
                'defaultHelpCommand' => true,
                'discordOptions' => [],
                'caseInsensitiveCommands' => false,
            ]);

        $options = $resolver->resolve($options);
        $options['prefixes'][] = $options['prefix'];

        return $options;
    }

    /**
     * Returns the command client options.
     *
     * @return array
     */
    public function getCommandClientOptions()
    {
        return $this->commandClientOptions;
    }

    /**
     * Handles dynamic get calls to the command client.
     *
     * @param string $name Variable name.
     *
     * @return mixed
     */
    public function __get(string $name)
    {
        $allowed = ['commands', 'aliases'];

        if (in_array($name, $allowed)) {
            return $this->{$name};
        }

        return parent::__get($name);
    }
}
