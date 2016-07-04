<?php

namespace Discord;

use Discord\CommandClient\Command;
use Discord\Discord;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscordCommandClient extends Discord
{
	protected $commandClientOptions;
	protected $commands = [];
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

			$this->on('message', function ($message) {
				if ($message->author->id == $this->id) {
					return;
				}

				if (substr($message->content, 0, strlen($this->commandClientOptions['prefix'])) == $this->commandClientOptions['prefix']) {
					$withoutPrefix = substr($message->content, strlen($this->commandClientOptions['prefix']));
					$args = explode(' ', $withoutPrefix);
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
		$this->commands[$command] = $commandInstance;

		foreach ($options['aliases'] as $alias) {
			$this->addCommandAlias($alias, $command);
		}

		return $commandInstance;
	}

	/**
	 * Adds a command alias.
	 *
	 * @param string $alias   The alias to add.
	 * @param string $command The command.
	 */
	public function addCommandAlias($alias, $command)
	{
		$this->aliases[$alias] = $command;
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
		} elseif (is_array($callable)) {
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
				'description' => '',
				'usage' => '',
				'aliases' => [],
			]);

		return $resolver->resolve($options);
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
				'discordOptions',
			])
			->setDefaults([
				'prefix' => '@mention ',
				'discordOptions' => [],
			]);

		return $resolver->resolve($options);
	}
}