<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets;

/**
 * This class contains all the handlers for the individual WebSocket events.
 */
class Handlers
{
    /**
     * An array of handlers.
     *
     * @var array Array of handlers.
     */
    protected $handlers = [];

    /**
     * Constructs the list of handlers.
     *
     * @return void
     */
    public function __construct()
    {
        // General
        $this->addHandler(Event::PRESENCE_UPDATE, \Discord\WebSockets\Events\PresenceUpdate::class);
        $this->addHandler(Event::TYPING_START, \Discord\WebSockets\Events\TypingStart::class);
        $this->addHandler(Event::VOICE_STATE_UPDATE, \Discord\WebSockets\Events\VoiceStateUpdate::class);
        $this->addHandler(Event::VOICE_SERVER_UPDATE, \Discord\WebSockets\Events\VoiceServerUpdate::class);

        // Guild Event handlers
        $this->addHandler(Event::GUILD_CREATE, \Discord\WebSockets\Events\GuildCreate::class);
        $this->addHandler(Event::GUILD_DELETE, \Discord\WebSockets\Events\GuildDelete::class);
        $this->addHandler(Event::GUILD_UPDATE, \Discord\WebSockets\Events\GuildUpdate::class);

        // Channel Event handlers
        $this->addHandler(Event::CHANNEL_CREATE, \Discord\WebSockets\Events\ChannelCreate::class);
        $this->addHandler(Event::CHANNEL_UPDATE, \Discord\WebSockets\Events\ChannelUpdate::class);
        $this->addHandler(Event::CHANNEL_DELETE, \Discord\WebSockets\Events\ChannelDelete::class);

        // Ban Event handlers
        $this->addHandler(Event::GUILD_BAN_ADD, \Discord\WebSockets\Events\GuildBanAdd::class);
        $this->addHandler(Event::GUILD_BAN_REMOVE, \Discord\WebSockets\Events\GuildBanRemove::class);

        // Message handlers
        $this->addHandler(Event::MESSAGE_CREATE, \Discord\WebSockets\Events\MessageCreate::class, ['message']);
        $this->addHandler(Event::MESSAGE_DELETE, \Discord\WebSockets\Events\MessageDelete::class);
        $this->addHandler(Event::MESSAGE_DELETE_BULK, \Discord\WebSockets\Events\MessageDeleteBulk::class);
        $this->addHandler(Event::MESSAGE_UPDATE, \Discord\WebSockets\Events\MessageUpdate::class);

        // New Member Event handlers
        $this->addHandler(Event::GUILD_MEMBER_ADD, \Discord\WebSockets\Events\GuildMemberAdd::class);
        $this->addHandler(Event::GUILD_MEMBER_REMOVE, \Discord\WebSockets\Events\GuildMemberRemove::class);
        $this->addHandler(Event::GUILD_MEMBER_UPDATE, \Discord\WebSockets\Events\GuildMemberUpdate::class);

        // New Role Event handlers
        $this->addHandler(Event::GUILD_ROLE_CREATE, \Discord\WebSockets\Events\GuildRoleCreate::class);
        $this->addHandler(Event::GUILD_ROLE_DELETE, \Discord\WebSockets\Events\GuildRoleDelete::class);
        $this->addHandler(Event::GUILD_ROLE_UPDATE, \Discord\WebSockets\Events\GuildRoleUpdate::class);
    }

    /**
     * Adds a handler to the list.
     *
     * @param string $event        The WebSocket event name.
     * @param string $classname    The Event class name.
     * @param array  $alternatives Alternative event names for the handler.
     *
     * @return void
     */
    public function addHandler($event, $classname, array $alternatives = [])
    {
        $this->handlers[$event] = [
            'class'        => $classname,
            'alternatives' => $alternatives,
        ];
    }

    /**
     * Returns a handler.
     *
     * @param string $event The WebSocket event name.
     *
     * @return string|null The Event class name or null;
     */
    public function getHandler($event)
    {
        if (isset($this->handlers[$event])) {
            return $this->handlers[$event];
        }
    }

    /**
     * Returns the handlers array.
     *
     * @return array Array of handlers.
     */
    public function getHandlers()
    {
        return $this->handlers;
    }

    /**
     * Returns the handlers.
     *
     * @return array Array of handler events.
     */
    public function getHandlerKeys()
    {
        return array_keys($this->handlers);
    }

    /**
     * Removes a handler.
     *
     * @param string $event The event handler to remove.
     *
     * @return void
     */
    public function removeHandler($event)
    {
        unset($this->handlers[$event]);
    }
}
