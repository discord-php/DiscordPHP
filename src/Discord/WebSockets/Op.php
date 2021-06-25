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

/**
 * Contains constants used in websockets.
 */
class Op
{
    // Dispatches an event.
    public const OP_DISPATCH = 0;
    // Used for ping checking.
    public const OP_HEARTBEAT = 1;
    // Used for client handshake.
    public const OP_IDENTIFY = 2;
    // Used to update the client presence.
    public const OP_PRESENCE_UPDATE = 3;
    // Used to join/move/leave voice channels.
    public const OP_VOICE_STATE_UPDATE = 4;
    // Used for voice ping checking.
    public const OP_VOICE_SERVER_PING = 5;
    // Used to resume a closed connection.
    public const OP_RESUME = 6;
    // Used to redirect clients to a new gateway.
    public const OP_RECONNECT = 7;
    // Used to request member chunks.
    public const OP_GUILD_MEMBER_CHUNK = 8;
    // Used to notify clients when they have an invalid session.
    public const OP_INVALID_SESSION = 9;
    // Used to pass through the heartbeat interval
    public const OP_HELLO = 10;
    // Used to acknowledge heartbeats.
    public const OP_HEARTBEAT_ACK = 11;

    ///////////////////////////////////////
    ///////////////////////////////////////
    ///////////////////////////////////////

    // Used to begin a voice WebSocket connection.
    public const VOICE_IDENTIFY = 0;
    // Used to select the voice protocol.
    public const VOICE_SELECT_PROTO = 1;
    // Used to complete the WebSocket handshake.
    public const VOICE_READY = 2;
    // Used to keep the WebSocket connection alive.e
    public const VOICE_HEARTBEAT = 3;
    // Used to describe the session.
    public const VOICE_DESCRIPTION = 4;
    // Used to identify which users are speaking.
    public const VOICE_SPEAKING = 5;
    // Sent by the Discord servers to acknowledge heartbeat
    public const VOICE_HEARTBEAT_ACK = 6;
    // Hello packet used to pass heartbeat interval
    public const VOICE_HELLO = 8;

    ///////////////////////////////////////
    ///////////////////////////////////////
    ///////////////////////////////////////

    // Normal close or heartbeat is invalid.
    public const CLOSE_NORMAL = 1000;
    // Abnormal close.
    public const CLOSE_ABNORMAL = 1006;
    // Unknown error.
    public const CLOSE_UNKNOWN_ERROR = 4000;
    // Unknown opcode was went.
    public const CLOSE_INVALID_OPCODE = 4001;
    // Invalid message was sent.
    public const CLOSE_INVALID_MESSAGE = 4002;
    // Not authenticated.
    public const CLOSE_NOT_AUTHENTICATED = 4003;
    // Invalid token on IDENTIFY.
    public const CLOSE_INVALID_TOKEN = 4004;
    // Already authenticated.
    public const CONST_ALREADY_AUTHD = 4005;
    // Session is invalid.
    public const CLOSE_INVALID_SESSION = 4006;
    // Invalid RESUME sequence.
    public const CLOSE_INVALID_SEQ = 4007;
    // Too many messages sent.
    public const CLOSE_TOO_MANY_MSG = 4008;
    // Session timeout.
    public const CLOSE_SESSION_TIMEOUT = 4009;
    // Invalid shard.
    public const CLOSE_INVALID_SHARD = 4010;
    // Sharding requred.
    public const CLOSE_SHARDING_REQUIRED = 4011;
    // Invalid API version.
    public const CLOSE_INVALID_VERSION = 4012;
    // Invalid intents.
    public const CLOSE_INVALID_INTENTS = 4013;
    // Disallowed intents.
    public const CLOSE_DISALLOWED_INTENTS = 4014;

    ///////////////////////////////////////
    ///////////////////////////////////////
    ///////////////////////////////////////

    // Can't find the server.
    public const CLOSE_VOICE_SERVER_NOT_FOUND = 4011;
    // Unknown protocol.
    public const CLOSE_VOICE_UNKNOWN_PROTO = 4012;
    // Disconnected from channel.
    public const CLOSE_VOICE_DISCONNECTED = 4014;
    // Voice server crashed.
    public const CLOSE_VOICE_SERVER_CRASH = 4015;
    // Unknown encryption mode.
    public const CLOSE_VOICE_UNKNOWN_ENCRYPT = 4016;

    /**
     * Returns the critical event codes that we should not reconnect after.
     *
     * @return array
     */
    public static function getCriticalCloseCodes(): array
    {
        return [
            self::CLOSE_INVALID_TOKEN,
            self::CLOSE_SHARDING_REQUIRED,
            self::CLOSE_INVALID_SHARD,
            self::CLOSE_INVALID_VERSION,
            self::CLOSE_INVALID_INTENTS,
            self::CLOSE_DISALLOWED_INTENTS,
        ];
    }

    /**
     * Returns the critical event codes for a voice websocket.
     *
     * @return array
     */
    public static function getCriticalVoiceCloseCodes(): array
    {
        return [
            self::CLOSE_INVALID_SESSION,
            self::CLOSE_INVALID_TOKEN,
            self::CLOSE_VOICE_SERVER_NOT_FOUND,
            self::CLOSE_VOICE_UNKNOWN_PROTO,
            self::CLOSE_VOICE_UNKNOWN_ENCRYPT,
        ];
    }
}
