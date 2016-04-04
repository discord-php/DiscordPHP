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

use Evenement\EventEmitterTrait;

/**
 * Contains constants for WebSocket events as well as handlers
 * for the events.
 */
class Event
{
    use EventEmitterTrait;

    // General
    const READY                = 'READY';
    const RESUMED              = 'RESUMED';
    const PRESENCE_UPDATE      = 'PRESENCE_UPDATE';
    const PRESENCES_REPLACE    = 'PRESENCES_REPLACE';
    const TYPING_START         = 'TYPING_START';
    const USER_SETTINGS_UPDATE = 'USER_SETTINGS_UPDATE';
    const VOICE_STATE_UPDATE   = 'VOICE_STATE_UPDATE';
    const VOICE_SERVER_UPDATE  = 'VOICE_SERVER_UPDATE';
    const GUILD_MEMBERS_CHUNK  = 'GUILD_MEMBERS_CHUNK';

    // Guild
    const GUILD_CREATE = 'GUILD_CREATE';
    const GUILD_DELETE = 'GUILD_DELETE';
    const GUILD_UPDATE = 'GUILD_UPDATE';

    const GUILD_BAN_ADD       = 'GUILD_BAN_ADD';
    const GUILD_BAN_REMOVE    = 'GUILD_BAN_REMOVE';
    const GUILD_MEMBER_ADD    = 'GUILD_MEMBER_ADD';
    const GUILD_MEMBER_REMOVE = 'GUILD_MEMBER_REMOVE';
    const GUILD_MEMBER_UPDATE = 'GUILD_MEMBER_UPDATE';
    const GUILD_ROLE_CREATE   = 'GUILD_ROLE_CREATE';
    const GUILD_ROLE_UPDATE   = 'GUILD_ROLE_UPDATE';
    const GUILD_ROLE_DELETE   = 'GUILD_ROLE_DELETE';

    // Channel
    const CHANNEL_CREATE = 'CHANNEL_CREATE';
    const CHANNEL_DELETE = 'CHANNEL_DELETE';
    const CHANNEL_UPDATE = 'CHANNEL_UPDATE';

    // Messages
    const MESSAGE_CREATE = 'MESSAGE_CREATE';
    const MESSAGE_DELETE = 'MESSAGE_DELETE';
    const MESSAGE_UPDATE = 'MESSAGE_UPDATE';

    /**
     * Returns the formatted data.
     *
     * @param array   $data    The data that was sent with the WebSocket.
     * @param Discord $discord The Discord instance.
     */
    public function getData($data, $discord)
    {
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed   $data
     * @param Discord $discord
     *
     * @return Discord
     */
    public function updateDiscordInstance($data, $discord)
    {
    }
}
