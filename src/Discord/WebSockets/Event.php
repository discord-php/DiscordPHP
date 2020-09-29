<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets;

use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Http\Http;
use Evenement\EventEmitterTrait;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Contains constants for WebSocket events as well as handlers
 * for the events.
 */
abstract class Event
{
    use EventEmitterTrait;

    // General
    const READY = 'READY';
    const RESUMED = 'RESUMED';
    const PRESENCE_UPDATE = 'PRESENCE_UPDATE';
    const PRESENCES_REPLACE = 'PRESENCES_REPLACE';
    const TYPING_START = 'TYPING_START';
    const USER_SETTINGS_UPDATE = 'USER_SETTINGS_UPDATE';
    const VOICE_STATE_UPDATE = 'VOICE_STATE_UPDATE';
    const VOICE_SERVER_UPDATE = 'VOICE_SERVER_UPDATE';
    const GUILD_MEMBERS_CHUNK = 'GUILD_MEMBERS_CHUNK';

    // Guild
    const GUILD_CREATE = 'GUILD_CREATE';
    const GUILD_DELETE = 'GUILD_DELETE';
    const GUILD_UPDATE = 'GUILD_UPDATE';

    const GUILD_BAN_ADD = 'GUILD_BAN_ADD';
    const GUILD_BAN_REMOVE = 'GUILD_BAN_REMOVE';

    const GUILD_MEMBER_ADD = 'GUILD_MEMBER_ADD';
    const GUILD_MEMBER_REMOVE = 'GUILD_MEMBER_REMOVE';
    const GUILD_MEMBER_UPDATE = 'GUILD_MEMBER_UPDATE';

    const GUILD_ROLE_CREATE = 'GUILD_ROLE_CREATE';
    const GUILD_ROLE_UPDATE = 'GUILD_ROLE_UPDATE';
    const GUILD_ROLE_DELETE = 'GUILD_ROLE_DELETE';

    const GUILD_INTEGRATIONS_UPDATE = 'GUILD_INTEGRATIONS_UPDATE';

    const INVITE_CREATE = 'INVITE_CREATE';
    const INVITE_DELETE = 'INVITE_DELETE';

    // Channel
    const CHANNEL_CREATE = 'CHANNEL_CREATE';
    const CHANNEL_DELETE = 'CHANNEL_DELETE';
    const CHANNEL_UPDATE = 'CHANNEL_UPDATE';
    const CHANNEL_PINS_UPDATE = 'CHANNEL_PINS_UPDATE';

    // Messages
    const MESSAGE_CREATE = 'MESSAGE_CREATE';
    const MESSAGE_DELETE = 'MESSAGE_DELETE';
    const MESSAGE_UPDATE = 'MESSAGE_UPDATE';
    const MESSAGE_DELETE_BULK = 'MESSAGE_DELETE_BULK';
    const MESSAGE_REACTION_ADD = 'MESSAGE_REACTION_ADD';
    const MESSAGE_REACTION_REMOVE = 'MESSAGE_REACTION_REMOVE';
    const MESSAGE_REACTION_REMOVE_ALL = 'MESSAGE_REACTION_REMOVE_ALL';
    const MESSAGE_REACTION_REMOVE_EMOJI = 'MESSAGE_REACTION_REMOVE_EMOJI';

    /**
     * The HTTP client.
     *
     * @var Http Client.
     */
    protected $http;

    /**
     * The Factory.
     *
     * @var Factory Factory.
     */
    protected $factory;

    /**
     * The Discord client instance.
     *
     * @var Discord Client.
     */
    protected $discord;

    /**
     * Constructs an event.
     *
     * @param Http    $http    The HTTP client.
     * @param Factory $factory The factory.
     * @param Discord $discord The Discord client.
     */
    public function __construct(Http $http, Factory $factory, Discord $discord)
    {
        $this->http = $http;
        $this->factory = $factory;
        $this->discord = $discord;
    }

    /**
     * Transforms the given data, and updates the
     * Discord instance if necessary.
     *
     * @param Deferred     $deferred The promise to use
     * @param array|object $data     The data that was sent with the WebSocket
     *
     * @return void|PromiseInterface
     */
    abstract public function handle(Deferred &$deferred, $data);

    public function __debugInfo(): array
    {
        return [];
    }
}
