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

namespace Discord;

use Discord\MessageCommandClient\CommandRegistry;
use Discord\MessageCommandClient\Command;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Modern, modular replacement for the legacy DiscordCommandClient.
 *
 * @since 10.49.0
 */
class MessageCommandClient extends Discord
{
    /**
     * Command registry storing commands and aliases.
     *
     * @var CommandRegistry
     */
    protected CommandRegistry $registry;

    /**
     * Construct the message command client.
     *
     * @param array $options Client options (see resolveOptions()).
     */
    public function __construct(array $options = [])
    {
        $this->options = $this->resolveOptions($options);

        $discordOptions = array_merge($this->options['discordOptions'] ?? [], ['token' => $this->options['token']]);

        parent::__construct($discordOptions);

        $this->registry = new CommandRegistry((bool) ($this->options['caseInsensitiveCommands'] ?? false));

        $this->on('init', function () {
            $this->preparePrefixes();

            $this->on('message', function ($message) {
                $this->handleMessage($message);
            });
        });

        if ($this->options['defaultHelpCommand']) {
            $this->registerCommand('help', function ($message, $args) {
                return $this->defaultHelpHandler($message, $args);
            }, [
                'description' => 'Provides a list of commands available.',
                'usage' => '[command]',
            ]);
        }
    }

    /**
     * Prepare and normalize prefixes, expanding @mention placeholders.
     */
    protected function preparePrefixes(): void
    {
        $this->options['prefix'] = str_replace('@mention', (string) $this->user, $this->options['prefix']);
        $this->options['name'] = str_replace('<UsernamePlaceholder>', $this->username, $this->options['name']);

        foreach ($this->options['prefixes'] as $key => $prefix) {
            if (strpos($prefix, '@mention') !== false) {
                $this->options['prefixes'][] = str_replace('@mention', "<@{$this->user->id}>", $prefix);
                $this->options['prefixes'][] = str_replace('@mention', "<@!{$this->user->id}>", $prefix);
                unset($this->options['prefixes'][$key]);
            }
        }
    }

    /**
     * Handle an incoming message event and dispatch commands.
     *
     * @param Message $message The received message.
     */
    protected function handleMessage(Message $message): void
    {
        if ($message->author->id === $this->id) {
            return;
        }

        $withoutPrefix = $this->checkForPrefix((string) $message->content);
        if ($withoutPrefix === null) {
            return;
        }

        $args = str_getcsv($withoutPrefix, ' ', '"', '\\');
        $commandName = array_shift($args);

        if ($commandName === null) {
            return;
        }

        if ($this->options['caseInsensitiveCommands']) {
            $commandName = function_exists('mb_strtolower')
                ? mb_strtolower($commandName)
                : strtolower($commandName);
        }

        $command = $this->registry->get($commandName);
        if ($command === null) {
            return;
        }

        $result = $command($message, $args);

        if (is_string($result)) {
            $message->reply($result)->then(null, $this->options['internalRejectedPromiseHandler']);
        }
    }

    /**
     * Check whether the given content starts with a configured prefix.
     *
     * @param string $content Message content.
     *
     * @return string|null The content without the prefix, or null when no prefix matched.
     */
    protected function checkForPrefix(string $content): ?string
    {
        foreach ($this->options['prefixes'] as $prefix) {
            $matchedContent = $this->removePrefixFromContent($content, (string) $prefix);
            if ($matchedContent !== null) {
                return $matchedContent;
            }
        }

        return null;
    }

    /**
     * Remove a prefix from content when it matches.
     *
     * @param string $content Message content.
     * @param string $prefix  Prefix to match.
     *
     * @return string|null Content without prefix, or null when no match.
     */
    protected function removePrefixFromContent(string $content, string $prefix): ?string
    {
        static $hasMbString = function_exists('mb_strlen') && function_exists('mb_substr');

        if ($hasMbString) {
            $len = mb_strlen($prefix);
            if (mb_substr($content, 0, $len) === $prefix) {
                return mb_substr($content, $len);
            }

            return null;
        }

        $len = strlen($prefix);
        if (substr($content, 0, $len) === $prefix) {
            return substr($content, $len);
        }

        return null;
    }

    /**
     * Register a new command.
     *
     * @param string                $name     Command trigger name.
     * @param callable|string|array $callable Callable, string or array to execute.
     * @param array                 $options  Command options.
     *
     * @return Command The registered command instance.
     */
    public function registerCommand(string $name, $callable, array $options = []): Command
    {
        if ($this->options['caseInsensitiveCommands']) {
            $name = function_exists('mb_strtolower')
                ? mb_strtolower($name)
                : strtolower($name);
        }

        ['command' => $commandInstance, 'options' => $resolvedOptions] = $this->buildCommand($name, $callable, $options);

        $this->registry->add($name, $commandInstance);

        foreach ($resolvedOptions['aliases'] as $alias) {
            if ($this->options['caseInsensitiveCommands'] && $alias !== null) {
                $alias = function_exists('mb_strtolower')
                    ? mb_strtolower($alias)
                    : strtolower($alias);
            }
            $this->registry->addAlias((string) $alias, $name);
        }

        return $commandInstance;
    }

    /**
     * Unregister an existing command.
     *
     * @param string $name Command name to remove.
     *
     * @throws \RuntimeException If the command does not exist.
     */
    public function unregisterCommand(string $name): void
    {
        if (! $this->registry->hasCommand($name)) {
            throw new \RuntimeException("A command with the name {$name} does not exist.");
        }

        $this->registry->remove($name);
    }

    /**
     * Register an alias for a command.
     *
     * @param string $alias   Alias to register.
     * @param string $command Target command name.
     */
    public function registerAlias(string $alias, string $command): void
    {
        $this->registry->addAlias($alias, $command);
    }

    /**
     * Unregister a command alias.
     *
     * @param string $alias Alias to remove.
     */
    public function unregisterCommandAlias(string $alias): void
    {
        $this->registry->removeAlias($alias);
    }

    /**
     * Get a command by name or alias.
     *
     * @param string $name Command name or alias.
     *
     * @return Command|null
     */
    public function getCommand(string $name): ?Command
    {
        return $this->registry->get($name);
    }

    /**
     * Build a Command instance from provided parameters.
     *
     * @param string                $name     Command name.
     * @param callable|string|array $callable Callable, string or array.
     * @param array                 $options  Command options.
     *
     * @return array{command: Command, options: array} Tuple of Command instance and resolved options.
     *
     * @throws \InvalidArgumentException When callable is not valid.
     */
    public function buildCommand(string $name, $callable, array $options = []): array
    {
        if (is_string($callable)) {
            $callable = fn ($message, array $args = []) => $callable;
        } elseif (is_array($callable) && ! is_callable($callable)) {
            $callable = fn ($message, array $args = []) => $callable[array_rand($callable)];
        }

        if (! is_callable($callable)) {
            throw new \InvalidArgumentException('The callable parameter must be a string, array or callable.');
        }

        $options = $this->resolveCommandOptions($options);

        $commandInstance = new Command(
            $this,
            $name,
            $callable,
            $options['description'],
            $options['longDescription'],
            $options['usage'],
            $options['cooldown'],
            $options['cooldownMessage'],
            $options['showHelp']
        );

        return [
            'command' => $commandInstance,
            'options' => $options,
        ];
    }

    /**
     * Resolve options for a single command.
     *
     * @param array $options Input options.
     *
     * @return array Resolved options.
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
                'showHelp',
            ])
            ->setDefaults([
                'description' => 'No description provided.',
                'longDescription' => '',
                'usage' => '',
                'aliases' => [],
                'cooldown' => 0,
                'cooldownMessage' => 'please wait %d second(s) to use this command again.',
                'showHelp' => true,
            ]);

        return $resolver->resolve($options);
    }

    /**
     * Resolve client-level options.
     *
     * @param array $options Input options.
     *
     * @return array Resolved options used by the client.
     */
    protected function resolveOptions(array $options = []): array
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
                'internalRejectedPromiseHandler',
            ])
            ->setAllowedTypes('internalRejectedPromiseHandler', ['null', 'callable'])
            ->setDefaults([
                'prefix' => '@mention ',
                'prefixes' => [],
                'name' => '<UsernamePlaceholder>',
                'description' => 'A bot made with DiscordPHP '.self::VERSION.'.',
                'defaultHelpCommand' => true,
                'discordOptions' => [],
                'caseInsensitiveCommands' => false,
                'internalRejectedPromiseHandler' => function ($reason): void {
                    if (is_string($reason) || $reason instanceof \Stringable) {
                        $this->getLogger()->error($reason);
                    } else {
                        $this->getLogger()->warning('Unhandled internal rejected promise, $reason is not a Throwable, '.gettype($reason).' given.');
                    }
                },
            ]);

        $resolved = $resolver->resolve($options);
        $resolved['prefixes'][] = $resolved['prefix'];

        return $resolved;
    }

    /**
     * Default help command handler.
     *
     * @param Message $message Message instance.
     * @param array   $args    Command path parts.
     *
     * @return mixed|null
     */
    protected function defaultHelpHandler($message, array $args)
    {
        $prefix = str_replace((string) $this->user, '@'.$this->username, $this->options['prefix']);

        if (count($args) > 0) {
            $command = null;
            $fullCommandString = implode(' ', $args);

            while (count($args) > 0) {
                $commandString = array_shift($args);

                if ($this->options['caseInsensitiveCommands']) {
                    $commandString = function_exists('mb_strtolower')
                        ? mb_strtolower($commandString)
                        : strtolower($commandString);
                }

                if ($command === null) {
                    $newCommand = $this->getCommand($commandString);
                } else {
                    $newCommand = $command->getCommand($commandString);
                }

                if ($newCommand === null) {
                    return "The command {$commandString} does not exist.";
                }

                $command = $newCommand;
            }

            $help = $command->getHelp($prefix);
            if (empty($help)) {
                return;
            }

            $embed = new Embed($this);
            $embed->setAuthor($this->options['name'], $this->client->user->avatar)
                ->setTitle($prefix.$fullCommandString.'\'s Help')
                ->setDescription(! empty($help['longDescription']) ? $help['longDescription'] : $help['description'])
                ->setFooter($this->options['name']);

            if (! empty($help['usage'])) {
                $embed->addFieldValues('Usage', '``'.$help['usage'].'``', true);
            }

            $message->channel->sendEmbed($embed)->then(null, $this->options['internalRejectedPromiseHandler']);

            return;
        }

        $embed = new Embed($this);
        $embed->setAuthor($this->options['name'], $this->client->avatar)
            ->setTitle($this->options['name'])
            ->setFooter($this->options['name']);

        $commandsDescription = '';
        $embedfields = [];
        foreach ($this->registry->all() as $command) {
            $help = $command->getHelp($prefix);
            if (empty($help)) {
                continue;
            }

            $embedfields[] = [
                'name' => $help['command'],
                'value' => $help['description'],
                'inline' => true,
            ];
            $commandsDescription .= "\n\n`".$help['command'].'`\n'.$help['description'];

            foreach ($help['subCommandsHelp'] as $subCommandHelp) {
                $embedfields[] = [
                    'name' => $subCommandHelp['command'],
                    'value' => $subCommandHelp['description'],
                    'inline' => true,
                ];
                $commandsDescription .= "\n\n`".$subCommandHelp['command'].'`\n'.$subCommandHelp['description'];
            }
        }

        if (count($embedfields) <= 25) {
            foreach ($embedfields as $field) {
                $embed->addField($field);
            }
            $commandsDescription = '';
        }

        $embed->setDescription(
            function_exists('mb_substr')
                ? mb_substr($this->options['description'].$commandsDescription, 0, 2048)
                : substr($this->options['description'].$commandsDescription, 0, 2048)
        );

        $message->channel->sendEmbed($embed)->then(null, $this->options['internalRejectedPromiseHandler']);
    }

    /**
     * Return the resolved client options.
     *
     * @return array<string,mixed>
     */
    public function getCommandClientOptions(): array
    {
        return $this->options;
    }

    /**
     * Return the command registry instance.
     *
     * @return CommandRegistry
     */
    public function getCommandRegistry(): CommandRegistry
    {
        return $this->registry;
    }
}
