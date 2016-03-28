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

use Cache\AdapterBundle\DependencyInjection\CacheAdapterExtension;
use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\Client;
use Discord\WebSockets\WebSocket;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The Discord class is the base of the client. This is the class that you
 * will start off with when you do anything with the client.
 *
 * @see \Discord\Parts\User\Client Most functions are forwarded onto the Client class.
 */
class Discord
{
    /**
     * The current version of the API.
     *
     * @var string The current version of the API.
     */
    const VERSION = 'v3.2.0-beta';

    /**
     * The Discord epoch value.
     *
     * @var int
     */
    const DISCORD_EPOCH = 1420070400000;

    /**
     * The Client instance.
     *
     * @var Client The Discord Client instance.
     */
    protected $client;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Logs into the Discord servers.
     *
     * @param string|array $options Either a token, or Options for the bot
     */
    public function __construct($options)
    {
        $options = !is_array($options) ? ['token' => $options] : $options;
        $options = $this->resolveOptions($options);

        $options = $this->resolveOptions($options);

        $this->container = $this->buildContainer($options);
    }

    /**
     * @param array $options
     *
     * @throws \Exception
     *
     * @return array
     */
    private function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired('token')
            ->setDefault('cache', $this->getDefaultCache())
            ->setAllowedTypes('token', 'string')
            ->setAllowedTypes('cache', 'array');

        $result = $resolver->resolve($options);

        return $result;
    }

    /**
     * Returns the date an object with an ID was created.
     *
     * @param Part|int $id The Part of ID to get the timestamp for.
     *
     * @return \Carbon\Carbon|null Carbon timestamp or null if can't be found.
     */
    public static function getTimestamp($id)
    {
        if ($id instanceof Part) {
            $id = $id->id;
        }

        if (!is_int($id)) {
            return;
        }

        $ms = ($id >> 22) + self::DISCORD_EPOCH;

        return new Carbon(date('r', $ms / 1000));
    }

    /**
     * Creates a Discord instance with a bot token.
     *
     * @param string $token The bot token.
     *
     * @return \Discord\Discord The Discord instance.
     */
    public static function createWithBotToken($token)
    {
        $discord = new self(['token' => $token]);

        return $discord;
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $name The function name.
     * @param array  $args The function arguments.
     *
     * @return mixed The result of the function.
     */
    public function __call($name, array $args = [])
    {
        return call_user_func_array([$this->getClient(), $name], $args);
    }

    /**
     * Handles dynamic variable calls to the class.
     *
     * @param string $name The variable name.
     *
     * @return mixed The variable or false if it does not exist.
     */
    public function __get($name)
    {
        return $this->getClient()->getAttribute($name);
    }

    /**
     * Handles dynamic set calls to the class.
     *
     * @param string $variable The variable name.
     * @param mixed  $value    The value to set.
     *
     * @return void
     */
    public function __set($variable, $value)
    {
        $this->getClient()->setAttribute($variable, $value);
    }

    /**
     * Returns a service from the container
     *
     * @param string $id Service ID
     *
     * @return mixed|object
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Returns a parameter from the container
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return $this->container->getParameter($name);
    }

    /**
     * @param array $options
     *
     * @return ContainerBuilder
     */
    private function buildContainer(array $options)
    {
        $options['version'] = static::VERSION;
        $container          = new ContainerBuilder(new ParameterBag($options));

        $cacheExtension = new CacheAdapterExtension();
        $container->registerExtension($cacheExtension);
        $container->loadFromExtension($cacheExtension->getAlias(), $options['cache']);

        $container->setDefinition('cache_wrapper.default', new Definition('Discord\Wrapper\CacheWrapper'))
            ->addArgument(new Reference('cache'));
        foreach (array_keys($options['cache']['providers']) as $name) {
            $container->setDefinition('cache_wrapper.'.$name, new Definition('Discord\Wrapper\CacheWrapper'))
                ->addArgument(new Reference('cache.provider.'.$name));
        }

        $container->set('discord', $this);

        $loader    = new XmlFileLoader($container, new FileLocator(__DIR__.'/Resources/config/'));
        $loader->load('services.xml');
        $loader->load('repositories.xml');

        $container->compile();

        return $container;
    }

    /**
     * @return WebSocket
     */
    public function getWebsocket()
    {
        return $this->get('websocket');
    }

    protected function getClient()
    {
        return $this->get('client');
    }

    /**
     * @return array
     */
    private function getDefaultCache()
    {
        return [
            'providers' => [
                'array' => [
                    'factory' => 'cache.factory.array',
                ],
            ],
        ];
    }
}
