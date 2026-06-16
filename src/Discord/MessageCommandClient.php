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

use Discord\MessageCommandClient\BuiltCommand;
use Discord\MessageCommandClient\CommandRegistry;
use Discord\MessageCommandClient\Command;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

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
    public function __construct(array $options = [], ?CommandRegistry $registry = null)
    {
        $this->options = $this->resolveOptions($options);

        $discordOptions = array_merge($this->options['discordOptions'] ?? [], ['token' => $this->options['token']]);

        parent::__construct($discordOptions);

        $this->registry = $registry ?? new CommandRegistry((bool) ($this->options['caseInsensitiveCommands'] ?? false));

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
     * Normalize a command or alias according to client options.
     */
    public function normalizeCommandName(string $name): string
    {
        if ($this->options['caseInsensitiveCommands']) {
            return function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name);
        }

        return $name;
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

        $commandName = $this->normalizeCommandName($commandName);

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
        $name = $this->normalizeCommandName($name);

        $built = $this->buildCommand($name, $callable, $options);
        if ($built instanceof BuiltCommand) {
            $commandInstance = $built->command;
            $resolvedOptions = $built->options;
        } else {
            ['command' => $commandInstance, 'options' => $resolvedOptions] = $built;
        }

        // Ensure no collision with existing command or alias names.
        if ($this->registry->has($name)) {
            throw new \RuntimeException("A command with the same name already exists.");
        }

        $this->registry->add($name, $commandInstance);

        foreach ($resolvedOptions['aliases'] as $alias) {
            if ($alias === null) {
                continue;
            }

            $aliasNormalized = $this->normalizeCommandName($alias);
            if ($this->registry->has($aliasNormalized)) {
                throw new \RuntimeException("An alias with the same name already exists.");
            }

            $this->registry->addAlias((string) $aliasNormalized, $name);
        }

        if (method_exists($this, 'emit')) {
            $this->emit('messagecommandclient.command.registered', [$name, $commandInstance, $resolvedOptions]);
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
            throw new \RuntimeException("A command with the same name does not exist.");
        }

        $this->registry->remove($name);
    }

    /**
     * Register an alias for a command.
     *
     * @param string $alias   Alias to register.
     * @param string $command Target command name.
     *
     * @throws \RuntimeException If the target command does not exist or alias is already taken.
     */
    public function registerAlias(string $alias, string $command): void
    {
        $aliasNormalized = $this->normalizeCommandName($alias);
        $commandNormalized = $this->normalizeCommandName($command);

        if ($this->registry->has($aliasNormalized)) {
            throw new \RuntimeException("An alias with the same name already exists.");
        }

        if (! $this->registry->hasCommand($commandNormalized)) {
            throw new \RuntimeException("A command with the same name does not exist.");
        }

        $this->registry->addAlias($aliasNormalized, $commandNormalized);
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
     * @return BuiltCommand DTO of Command instance and resolved options.
     *
     * @throws \InvalidArgumentException When callable is not valid.
     */
    public function buildCommand(string $name, $callable, array $options = []): BuiltCommand
    {
        if (is_string($callable)) {
            $callable = fn ($message, array $args = []) => $callable;
        } elseif (is_array($callable) && ! is_callable($callable)) {
            $callable = fn ($message, array $args = []) => $callable[array_rand($callable)];
        }

        if (! is_callable($callable)) {
            throw new \InvalidArgumentException('The callable parameter must be a string, array or callable.');
        }

        // Wrap user-provided callable so it can be safely invoked with
        // 0/1/2 parameters depending on its declared arity. This prevents
        // ArgumentCountError when users register callables that accept
        // fewer parameters than the invocation site provides.
        $callable = $this->wrapRegisteredCallable($callable);

        $options = $this->resolveCommandOptions($options);

        $commandInstance = new Command(
            $this,
            $name,
            $callable,
            $options
        );

        return new BuiltCommand($commandInstance, $options);
    }

    /**
     * Wrap a registered callable to adapt invocation based on its declared arity.
     *
     * The wrapper will call the original callable with 0, 1 or 2 arguments
     * depending on how many required parameters the callable declares. This
     * avoids runtime ArgumentCountError when users register callables that
     * accept fewer parameters than the command invocation provides.
     *
     * @param callable $callable
     *
     * @return callable
     */
    protected function wrapRegisteredCallable(callable $callable): callable
    {
        try {
            // Normalize any callable to a Closure first so invokable objects,
            // callable strings and arrays are all handled uniformly.
            $closure = \Closure::fromCallable($callable);
            $ref = new \ReflectionFunction($closure);

            $required = $ref->getNumberOfRequiredParameters();
            $isVariadic = $ref->isVariadic();
        } catch (\Throwable $e) {
            // If reflection fails for any reason (including TypeError for
            // invokable objects on older reflection paths), fall back to a
            // safe default that preserves previous behavior.
            $required = 2;
            $isVariadic = false;
        }

        return function ($message, array $args = []) use ($callable, $required, $isVariadic) {
            if ($isVariadic) {
                // If callable is variadic, prefer to expand $args after the
                // message so the handler can accept multiple individual args.
                return call_user_func_array($callable, array_merge([$message], $args));
            }

            if ($required === 0) {
                return call_user_func($callable);
            }

            if ($required === 1) {
                return call_user_func($callable, $message);
            }

            return call_user_func($callable, $message, $args);
        };
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
            ])
            ->setAllowedTypes('aliases', ['array', 'string', 'null'])
            ->setNormalizer('aliases', function (Options $options, $value) {
                if ($value === null) {
                    return [];
                }

                if (! is_array($value)) {
                    $value = [$value];
                }

                $sanitized = [];
                foreach ($value as $alias) {
                    if (is_scalar($alias) || (is_object($alias) && method_exists($alias, '__toString'))) {
                        $aliasStr = trim((string) $alias);
                        if ($aliasStr !== '') {
                            $sanitized[] = $aliasStr;
                        }
                    }
                }

                return array_values(array_unique($sanitized));
            });

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
