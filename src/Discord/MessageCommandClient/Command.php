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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use Discord\MessageCommandClient\BuiltCommand;

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
     * Managed by CooldownManager.
     *
     * @var CooldownManager
     */
    protected CooldownManager $cooldownManager;

    /**
     * Sub-commands map: name => Command.
     *
     * Managed by SubCommandRegistry.
     *
     * @var SubCommandRegistry
     */
    protected SubCommandRegistry $subCommandRegistry;

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
     * An array of options passed to the command.
     *
     * @var array
     */
    protected $options;

    /**
     * Creates a command instance.
     *
     * @param MessageCommandClient $client                     The message command client.
     * @param string               $command                    Command trigger.
     * @param callable             $callable                   Callable to execute.
     * @param array                $options                    Command options:
     * @param string               $options['description']     Short description.
     * @param string               $options['longDescription'] Long description.
     * @param string               $options['usage']           Usage string.
     * @param int                  $options['cooldown']        Cooldown in milliseconds.
     * @param string               $options['cooldownMessage'] Cooldown message.
     * @param bool                 $options['showHelp']        Whether to show in help.
     */
    public function __construct(
        MessageCommandClient $client,
        string $command,
        callable $callable,
        array $options = []
    ) {
        $this->client = $client;
        $this->command = $command;
        $this->callable = $callable;
        
        $options = $this->resolveOptions($options);

        $this->options = $options;
        $this->description = $options['description'];
        $this->longDescription = $options['longDescription'];
        $this->usage = $options['usage'];
        $this->cooldown = $options['cooldown'];
        $this->cooldownMessage = $options['cooldownMessage'];
        $this->showHelp = $options['showHelp'];
        $this->subCommandRegistry = new SubCommandRegistry(fn (string $name) => $this->normalizeName($name));
        $this->cooldownManager = new CooldownManager();
    }

    /**
     * Resolve and normalize command options.
     *
     * @param array $options
     *
     * @return array
     */
    protected function resolveOptions(array $options = []): array
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
     * Normalize a command or alias according to the client's case sensitivity setting.
     */
    protected function normalizeName(string $name): string
    {
        if (method_exists($this->client, 'normalizeCommandName')) {
            return $this->client->normalizeCommandName($name);
        }

        // Fallback: mirror previous behavior if client does not provide helper.
        if ($this->client->getCommandClientOptions()['caseInsensitiveCommands']) {
            return function_exists('mb_strtolower') ? mb_strtolower($name) : strtolower($name);
        }

        return $name;
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
        return $this->subCommandRegistry->get($command, $aliases);
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
        $built = $this->client->buildCommand($command, $callable, $options);
        if ($built instanceof BuiltCommand) {
            $commandInstance = $built->command;
            $resolvedOptions = $built->options;
        } else {
            ['command' => $commandInstance, 'options' => $resolvedOptions] = $built;
        }

        $key = $this->subCommandRegistry->register($commandInstance, $resolvedOptions['aliases']);

        $this->client->emit('subcommand-registered', [$this->command, $key, $commandInstance, $resolvedOptions]);

        return $commandInstance;
    }

    /**
     * Unregister a sub-command.
     *
     * @param string $command Sub-command name.
     * 
     * @throws \RuntimeException If the sub-command does not exist.
     */
    public function unregisterSubCommand(string $command): void
    {
        $this->subCommandRegistry->unregister($command);
    }

    /**
     * Register an alias for a sub-command.
     *
     * @param string $alias   Alias name.
     * @param string $command Target sub-command name.
     * 
     * @throws \RuntimeException If the alias or target command name conflicts with existing sub-commands or aliases.
     */
    public function registerSubCommandAlias(string $alias, string $command): void
    {
        $this->subCommandRegistry->registerAlias($alias, $command);
    }

    /**
     * Unregister a sub-command alias.
     *
     * @param string $alias Alias name.
     */
    public function unregisterSubCommandAlias(string $alias): void
    {
        $this->subCommandRegistry->unregisterAlias($alias);
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

        if ($subCommand !== null) {
            $subCommand = $this->normalizeName($subCommand);
        }

        $found = $this->subCommandRegistry->get($subCommand, true);
        if ($found instanceof Command) {
            return $found->handle($message, $args);
        }

        if (null !== $subCommand) {
            array_unshift($args, $originalSubCommand);
        }

        $currentTime = (int) round(microtime(true) * 1000);

        if (($cooldownResult = $this->cooldownManager->enforce($message, $currentTime, $this->cooldown, $this->cooldownMessage)) !== null) {
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

        // Deprecated: functionality moved to CooldownManager.
        return $this->cooldownManager->enforce($message, $currentTime, $this->cooldown, $this->cooldownMessage);
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

        foreach ($this->subCommandRegistry->all() as $command) {
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
