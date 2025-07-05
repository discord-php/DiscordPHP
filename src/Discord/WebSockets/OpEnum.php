<?php

declare(strict_types=1);

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
 *
 * @link https://discord.com/developers/docs/topics/opcodes-and-status-codes
 *
 * @since 3.2.1
 */
enum OpEnum: int
{
    /**
     * Gateway Opcodes.
     *
     * All gateway events in Discord are tagged with an opcode that denotes the
     * payload type. Your connection to our gateway may also sometimes close.
     * When it does, you will receive a close code that tells you what happened.
     *
     * @link https://discord.com/developers/docs/topics/opcodes-and-status-codes#gateway-gateway-opcodes
     */

    /** Dispatches an event. */
    case OP_DISPATCH = 0;
    /** Used for ping checking. */
    case OP_HEARTBEAT = 1;
    /** Used for client handshake. */
    case OP_IDENTIFY = 2;
    /** Used to update the client presence. */
    case OP_PRESENCE_UPDATE = 3;
    /** Used to join/move/leave voice channels. */
    case OP_VOICE_STATE_UPDATE = 4;
    /** Used for voice ping checking. */
    case OP_VOICE_SERVER_PING = 5;
    /** Used to resume a closed connection. */
    case OP_RESUME = 6;
    /** Used to redirect clients to a new gateway. */
    case OP_RECONNECT = 7;
    /** Used to request member chunks. */
    case OP_GUILD_MEMBER_CHUNK = 8;
    /** Used to notify clients when they have an invalid session. */
    case OP_INVALID_SESSION = 9;
    /** Used to pass through the heartbeat interval. */
    case OP_HELLO = 10;
    /** Used to acknowledge heartbeats. */
    case OP_HEARTBEAT_ACK = 11;
    /** Request soundboard sounds. */
    case REQUEST_SOUNDBOARD_SOUNDS = 31;

    /**
     * Voice Opcodes.
     *
     * Our voice gateways have their own set of opcodes and close codes.
     *
     * @link https://discord.com/developers/docs/topics/opcodes-and-status-codes#voice-voice-opcodes
     */

    /** Used to begin a voice WebSocket connection. */
    case VOICE_IDENTIFY = 0;
    /** Used to select the voice protocol. */
    case VOICE_SELECT_PROTO = 1;
    /** Used to complete the WebSocket handshake. */
    case VOICE_READY = 2;
    /** Used to keep the WebSocket connection alive. */
    case VOICE_HEARTBEAT = 3;
    /** Used to describe the session. */
    case VOICE_DESCRIPTION = 4;
    /** Used to identify which users are speaking. */
    case VOICE_SPEAKING = 5;
    /** Sent by the Discord servers to acknowledge heartbeat */
    case VOICE_HEARTBEAT_ACK = 6;
    /** Resume a connection. */
    case VOICE_RESUME = 7;
    /** Hello packet used to pass heartbeat interval */
    case VOICE_HELLO = 8;
    /** Acknowledge a successful session resume. */
    case VOICE_RESUMED = 9;
    /** One or more clients have connected to the voice channel */
    case VOICE_CLIENTS_CONNECT = 11;
    case VOICE_CLIENT_CONNECT = 11; // Deprecated, used VOICE_CLIENTS_CONNECT instead
    /** A client has disconnected from the voice channel. */
    case VOICE_CLIENT_DISCONNECT = 13;
    /** Was not documented within the op codes and statuses*/
    case VOICE_CLIENT_UNKNOWN_15 = 15;
    case VOICE_CLIENT_UNKNOWN_18 = 18;
    /** NOT DOCUMENTED - Assumed to be the platform type in which the user is. */
    case VOICE_CLIENT_PLATFORM = 20;
    /** A downgrade from the DAVE protocol is upcoming. */
    case VOICE_DAVE_PREPARE_TRANSITION = 21;
    /** Execute a previously announced protocol transition. */
    case VOICE_DAVE_EXECUTE_TRANSITION = 22;
    /** Acknowledge readiness previously announced transition. */
    case VOICE_DAVE_TRANSITION_READY = 23;
    /** A DAVE protocol version or group change is upcoming. */
    case VOICE_DAVE_PREPARE_EPOCH = 24;
    /** Credential and public key for MLS external sender. */
    case VOICE_DAVE_MLS_EXTERNAL_SENDER = 25;
    /** MLS Key Package for pending group member. */
    case VOICE_DAVE_MLS_KEY_PACKAGE = 26;
    /** MLS Proposals to be appended or revoked. */
    case VOICE_DAVE_MLS_PROPOSALS = 27;
    /** MLS Commit with optional MLS Welcome messages. */
    case VOICE_DAVE_MLS_COMMIT_WELCOME = 28;
    /** MLS Commit to be processed for upcoming transition. */
    case VOICE_DAVE_MLS_ANNOUNCE_COMMIT_TRANSITION = 29;
    /** MLS Welcome to group for upcoming transition. */
    case VOICE_DAVE_MLS_WELCOME = 30;
    /** Flag invalid commit or welcome, request re-add */
    case VOICE_DAVE_MLS_INVALID_COMMIT_WELCOME = 31;

    /**
     * Gateway Close Event Codes.
     *
     * In order to prevent broken reconnect loops, you should consider some
     * close codes as a signal to stop reconnecting. This can be because your
     * token expired, or your identification is invalid. This table explains
     * what the application defined close codes for the gateway are, and which
     * close codes you should not attempt to reconnect.
     *
     * @link https://discord.com/developers/docs/topics/opcodes-and-status-codes#gateway-gateway-close-event-codes
     */

    /** Normal close or heartbeat is invalid. */
    case CLOSE_NORMAL = 1000;
    /** Abnormal close. */
    case CLOSE_ABNORMAL = 1006;
    /** Unknown error. */
    case CLOSE_UNKNOWN_ERROR = 4000;
    /** Unknown opcode was sent. */
    case CLOSE_INVALID_OPCODE = 4001;
    /** Invalid message was sent. */
    case CLOSE_INVALID_MESSAGE = 4002;
    /** Not authenticated. */
    case CLOSE_NOT_AUTHENTICATED = 4003;
    /** Invalid token on IDENTIFY. */
    case CLOSE_INVALID_TOKEN = 4004;
    /** Already authenticated. */
    case CONST_ALREADY_AUTHD = 4005;
    /** Session is invalid. */
    case CLOSE_INVALID_SESSION = 4006;
    /** Invalid RESUME sequence. */
    case CLOSE_INVALID_SEQ = 4007;
    /** Too many messages sent. */
    case CLOSE_TOO_MANY_MSG = 4008;
    /** Session timeout. */
    case CLOSE_SESSION_TIMEOUT = 4009;
    /** Invalid shard. */
    case CLOSE_INVALID_SHARD = 4010;
    /** Sharding required. */
    case CLOSE_SHARDING_REQUIRED = 4011;
    /** Invalid API version. */
    case CLOSE_INVALID_VERSION = 4012;
    /** Invalid intents. */
    case CLOSE_INVALID_INTENTS = 4013;
    /** Disallowed intents. */
    case CLOSE_DISALLOWED_INTENTS = 4014;

    /**
     * Voice Close Event Codes.
     *
     * @link https://discord.com/developers/docs/topics/opcodes-and-status-codes#voice-voice-close-event-codes
     */

    /** Can't find the server. */
    case CLOSE_VOICE_SERVER_NOT_FOUND = 4011;
    /** Unknown protocol. */
    case CLOSE_VOICE_UNKNOWN_PROTO = 4012;
    /** Disconnected from channel. */
    case CLOSE_VOICE_DISCONNECTED = 4014;
    /** Voice server crashed. */
    case CLOSE_VOICE_SERVER_CRASH = 4015;
    /** Unknown encryption mode. */
    case CLOSE_VOICE_UNKNOWN_ENCRYPT = 4016;

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

    public static function getVoiceCodes(): array
    {
        return [
            self::VOICE_IDENTIFY,
            self::VOICE_SELECT_PROTO,
            self::VOICE_READY,
            self::VOICE_HEARTBEAT,
            self::VOICE_DESCRIPTION,
            self::VOICE_SPEAKING,
            self::VOICE_HEARTBEAT_ACK,
            self::VOICE_RESUME,
            self::VOICE_HELLO,
            self::VOICE_RESUMED,
            self::VOICE_CLIENTS_CONNECT,
            self::VOICE_CLIENT_CONNECT,
            self::VOICE_CLIENT_DISCONNECT,
            self::VOICE_CLIENT_UNKNOWN_15,
            self::VOICE_CLIENT_UNKNOWN_18,
            self::VOICE_CLIENT_PLATFORM,
            self::VOICE_DAVE_PREPARE_TRANSITION,
            self::VOICE_DAVE_EXECUTE_TRANSITION,
            self::VOICE_DAVE_TRANSITION_READY,
            self::VOICE_DAVE_PREPARE_EPOCH,
            self::VOICE_DAVE_MLS_EXTERNAL_SENDER,
            self::VOICE_DAVE_MLS_KEY_PACKAGE,
            self::VOICE_DAVE_MLS_PROPOSALS,
            self::VOICE_DAVE_MLS_COMMIT_WELCOME,
            self::VOICE_DAVE_MLS_ANNOUNCE_COMMIT_TRANSITION,
            self::VOICE_DAVE_MLS_WELCOME,
            self::VOICE_DAVE_MLS_INVALID_COMMIT_WELCOME,
        ];
    }

    public static function getGatewayCodes(): array
    {
        return [
            self::OP_DISPATCH,
            self::OP_HEARTBEAT,
            self::OP_IDENTIFY,
            self::OP_PRESENCE_UPDATE,
            self::OP_VOICE_STATE_UPDATE,
            self::OP_VOICE_SERVER_PING,
            self::OP_RESUME,
            self::OP_RECONNECT,
            self::OP_GUILD_MEMBER_CHUNK,
            self::OP_INVALID_SESSION,
            self::OP_HELLO,
            self::OP_HEARTBEAT_ACK,
            self::REQUEST_SOUNDBOARD_SOUNDS,
        ];
    }

    public static function getAllCodes(): array
    {
        return array_merge(
            self::getGatewayCodes(),
            self::getVoiceCodes()
        );
    }

    public static function isVoiceCode(int $code): bool
    {
        return in_array($code, self::getVoiceCodes(), true);
    }

    public static function isGatewayCode(int $code): bool
    {
        return in_array($code, self::getGatewayCodes(), true);
    }

    public static function isValidCode(int $code): bool
    {
        return in_array($code, self::getAllCodes(), true);
    }

    public static function isCriticalCloseCode(int $code): bool
    {
        return in_array($code, self::getCriticalCloseCodes(), true);
    }

    public static function isCriticalVoiceCloseCode(int $code): bool
    {
        return in_array($code, self::getCriticalVoiceCloseCodes(), true);
    }

    public static function isValidOpCode(int $code): bool
    {
        return self::isGatewayCode($code) || self::isVoiceCode($code);
    }

    public static function isValidCloseCode(int $code): bool
    {
        return self::isCriticalCloseCode($code) || self::isCriticalVoiceCloseCode($code);
    }

    public static function isValidOp(int $code): bool
    {
        return self::isValidOpCode($code) || self::isValidCloseCode($code);
    }

    public static function voiceCodeToString(
        ?self $code = null,
        bool $snakeCase = false,
        bool $pluckVoicePrefix = true
    ): string
    {
        $code ??= $code?->value;
        if (!$code instanceof self && !self::isVoiceCode($code)) {
            return '';
        }
        $name = self::from($code)->name;

        if ($pluckVoicePrefix) {
            $name = str_replace('VOICE_', '', $name);
        }

        if ($snakeCase) {
            return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));
        }

        return $name;
    }
}
