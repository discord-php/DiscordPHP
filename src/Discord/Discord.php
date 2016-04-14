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

use Carbon\Carbon;
use Discord\Helpers\Guzzle;
use Discord\Parts\Part;
use Discord\Parts\User\Client;
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
     * Logs into the Discord servers.
     *
     * @param string|array $options Either a token, or Options for the bot
     */
    public function __construct($options)
    {
        $options = ! is_array($options) ? ['token' => $options] : $options;
        $options = $this->resolveOptions($options);

        define('DISCORD_TOKEN', $options['token']);

        $this->client = new Client((array) Guzzle::get('users/@me'), true);
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Exception
     */
    private function resolveOptions(array $options)
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setRequired('token')
            ->setAllowedTypes('token', 'string');

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

        if (! is_int($id)) {
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
        $discord = new self($token);

        return $discord;
    }

    /**
     * Handles dynamic calls to the class.
     *
     * @param string $name The function name.
     * @param array  $name The function arguments.
     *
     * @return mixed The result of the function.
     */
    public function __call($name, array $args = [])
    {
        if (is_null($this->client)) {
            return false;
        }

        return call_user_func_array([$this->client, $name], $args);
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
        if (is_null($this->client)) {
            return false;
        }

        return $this->client->getAttribute($name);
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
        $this->client->setAttribute($variable, $value);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }
}
