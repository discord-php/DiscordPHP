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
use Discord\Exceptions\InviteInvalidException;
use Discord\Helpers\Guzzle;
use Discord\Parts\Guild\Invite;
use Discord\Parts\Part;
use Discord\Parts\User\Client;

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
     * @param string $token The bot account's token.
     *
     * @return void
     */
    public function __construct($token)
    {
        @define('DISCORD_TOKEN', "Bot {$token}");

        $request = Guzzle::get('users/@me');

        $this->client = new Client((array) $request, true);
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
}
