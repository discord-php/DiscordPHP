<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets;

use Discord\Discord;
use Discord\Factory\Factory;
use Discord\Http\Http;
use Discord\Helpers\Deferred;
use Evenement\EventEmitterTrait;
use React\Promise\PromiseInterface;

/**
 * Contains constants for WebSocket events as well as handlers
 * for the events.
 */
abstract class Event
{
    use EventEmitterTrait;

    // General
    public const READY = 'READY';
    public const RESUMED = 'RESUMED';
    public const PRESENCE_UPDATE = 'PRESENCE_UPDATE';
    public const PRESENCES_REPLACE = 'PRESENCES_REPLACE';
    public const TYPING_START = 'TYPING_START';
    public const USER_SETTINGS_UPDATE = 'USER_SETTINGS_UPDATE';
    public const VOICE_STATE_UPDATE = 'VOICE_STATE_UPDATE';
    public const VOICE_SERVER_UPDATE = 'VOICE_SERVER_UPDATE';
    public const GUILD_MEMBERS_CHUNK = 'GUILD_MEMBERS_CHUNK';
    public const INTERACTION_CREATE = 'INTERACTION_CREATE';

    // Guild
    public const GUILD_CREATE = 'GUILD_CREATE';
    public const GUILD_DELETE = 'GUILD_DELETE';
    public const GUILD_UPDATE = 'GUILD_UPDATE';

    public const GUILD_BAN_ADD = 'GUILD_BAN_ADD';
    public const GUILD_BAN_REMOVE = 'GUILD_BAN_REMOVE';

    public const GUILD_MEMBER_ADD = 'GUILD_MEMBER_ADD';
    public const GUILD_MEMBER_REMOVE = 'GUILD_MEMBER_REMOVE';
    public const GUILD_MEMBER_UPDATE = 'GUILD_MEMBER_UPDATE';

    public const GUILD_ROLE_CREATE = 'GUILD_ROLE_CREATE';
    public const GUILD_ROLE_UPDATE = 'GUILD_ROLE_UPDATE';
    public const GUILD_ROLE_DELETE = 'GUILD_ROLE_DELETE';

    public const GUILD_INTEGRATIONS_UPDATE = 'GUILD_INTEGRATIONS_UPDATE';

    public const INVITE_CREATE = 'INVITE_CREATE';
    public const INVITE_DELETE = 'INVITE_DELETE';

    // Channel
    public const CHANNEL_CREATE = 'CHANNEL_CREATE';
    public const CHANNEL_DELETE = 'CHANNEL_DELETE';
    public const CHANNEL_UPDATE = 'CHANNEL_UPDATE';
    public const CHANNEL_PINS_UPDATE = 'CHANNEL_PINS_UPDATE';

    // Messages
    public const MESSAGE_CREATE = 'MESSAGE_CREATE';
    public const MESSAGE_DELETE = 'MESSAGE_DELETE';
    public const MESSAGE_UPDATE = 'MESSAGE_UPDATE';
    public const MESSAGE_DELETE_BULK = 'MESSAGE_DELETE_BULK';
    public const MESSAGE_REACTION_ADD = 'MESSAGE_REACTION_ADD';
    public const MESSAGE_REACTION_REMOVE = 'MESSAGE_REACTION_REMOVE';
    public const MESSAGE_REACTION_REMOVE_ALL = 'MESSAGE_REACTION_REMOVE_ALL';
    public const MESSAGE_REACTION_REMOVE_EMOJI = 'MESSAGE_REACTION_REMOVE_EMOJI';

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
