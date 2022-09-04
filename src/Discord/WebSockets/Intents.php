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

class Intents
{
    /**
     * Guilds intent:.
     *
     * - GUILD_CREATE
     * - GUILD_UPDATE
     * - GUILD_DELETE
     * - GUILD_ROLE_CREATE
     * - GUILD_ROLE_UPDATE
     * - GUILD_ROLE_DELETE
     * - CHANNEL_CREATE
     * - CHANNEL_UPDATE
     * - CHANNEL_DELETE
     * - CHANNEL_PINS_UPDATE
     * - STAGE_INSTANCE_CREATE
     * - STAGE_INSTANCE_UPDATE
     * - STAGE_INSTANCE_DELETE
     */
    public const GUILDS = (1 << 0);

    /**
     * Guild member events:.
     *
     * - GUILD_MEMBER_ADD
     * - GUILD_MEMBER_UPDATE
     * - GUILD_MEMBER_REMOVE
     */
    public const GUILD_MEMBERS = (1 << 1);

    /**
     * Guild ban events:.
     *
     * - GUILD_BAN_ADD
     * - GUILD_BAN_REMOVE
     */
    public const GUILD_BANS = (1 << 2);

    /**
     * Guild emoji and sticker events:.
     *
     * - GUILD_EMOJIS_UPDATE
     * - GUILD_STICKERS_UPDATE
     */
    public const GUILD_EMOJIS_AND_STICKERS = (1 << 3);

    /**
     * Guild integration events:.
     *
     * - GUILD_INTEGRATIONS_UPDATE
     * - INTEGRATION_CREATE
     * - INTEGRATION_UPDATE
     * - INTEGRATION_DELETE
     */
    public const GUILD_INTEGRATIONS = (1 << 4);

    /**
     * Guild webhook events.
     *
     * - WEBHOOKS_UPDATE
     */
    public const GUILD_WEBHOOKS = (1 << 5);

    /**
     * Guild invite events:.
     *
     * - INVITE_CREATE
     * - INVITE_DELETE
     */
    public const GUILD_INVITES = (1 << 6);

    /**
     * Guild voice state events:.
     *
     * - VOICE_STATE_UPDATE
     */
    public const GUILD_VOICE_STATES = (1 << 7);

    /**
     * Guild presence events:.
     *
     * - PRESENCE_UPDATE
     */
    public const GUILD_PRESENCES = (1 << 8);

    /**
     * Guild message events:.
     *
     * - MESSAGE_CREATE
     * - MESSAGE_UPDATE
     * - MESSAGE_DELETE
     * - MESSAGE_DELETE_BULK
     */
    public const GUILD_MESSAGES = (1 << 9);

    /**
     * Guild message reaction events:.
     *
     * - MESSAGE_REACTION_ADD
     * - MESSAGE_REACTION_REMOVE
     * - MESSAGE_REACTION_REMOVE_ALL
     * - MESSAGE_REACTION_REMOVE_EMOJI
     */
    public const GUILD_MESSAGE_REACTIONS = (1 << 10);

    /**
     * Guild typing events:.
     *
     * - TYPING_START
     */
    public const GUILD_MESSAGE_TYPING = (1 << 11);

    /**
     * Direct message events:.
     *
     * - CHANNEL_CREATE
     * - MESSAGE_CREATE
     * - MESSAGE_UPDATE
     * - MESSAGE_DELETE
     * - CHANNEL_PINS_UPDATE
     */
    public const DIRECT_MESSAGES = (1 << 12);

    /**
     * Direct message reaction events:.
     *
     * - MESSAGE_REACTION_ADD
     * - MESSAGE_REACTION_REMOVE
     * - MESSAGE_REACTION_REMOVE_ALL
     * - MESSAGE_REACTION_REMOVE_EMOJI
     */
    public const DIRECT_MESSAGE_REACTIONS = (1 << 13);

    /**
     * Direct message typing events:.
     *
     * - TYPING_START
     */
    public const DIRECT_MESSAGE_TYPING = (1 << 14);

    public const MESSAGE_CONTENT = (1 << 15);

    /**
     * Guild scheduled events events:.
     *
     * - GUILD_SCHEDULED_EVENT_CREATE
     * - GUILD_SCHEDULED_EVENT_UPDATE
     * - GUILD_SCHEDULED_EVENT_DELETE
     * - GUILD_SCHEDULED_EVENT_USER_ADD
     * - GUILD_SCHEDULED_EVENT_USER_REMOVE
     */
    public const GUILD_SCHEDULED_EVENTS = (1 << 16);

    /**
     * Auto moderation rule events:.
     *
     * - AUTO_MODERATION_RULE_CREATE
     * - AUTO_MODERATION_RULE_UPDATE
     * - AUTO_MODERATION_RULE_DELETE
     */
    public const AUTO_MODERATION_CONFIGURATION = (1 << 20);

    /**
     * Auto moderation execution events:.
     *
     * - AUTO_MODERATION_ACTION_EXECUTION
     */
    public const AUTO_MODERATION_EXECUTION = (1 << 21);

    /**
     * Returns an array of valid intents.
     *
     * @return array
     */
    public static function getValidIntents(): array
    {
        $reflect = new \ReflectionClass(__CLASS__);

        return array_values($reflect->getConstants());
    }

    /**
     * Returns an integer value that represents all intents.
     *
     * @return int
     */
    public static function getAllIntents(): int
    {
        $intentVal = 0;

        foreach (self::getValidIntents() as $intent) {
            $intentVal |= $intent;
        }

        return $intentVal;
    }

    /**
     * Returns an integer value that represents the default intents.
     * This is all intents minus the privileged intents.
     *
     * @return int
     */
    public static function getDefaultIntents(): int
    {
        return static::getAllIntents() & ~(static::GUILD_MEMBERS | static::GUILD_PRESENCES | static::MESSAGE_CONTENT);
    }

    /**
     * Converts an integer intent representation into an array of strings,
     * representing the enabled intents. Useful for debugging.
     *
     * @param int $intents
     *
     * @return string[]
     */
    public static function getIntentArray(int $intents): array
    {
        $results = [];
        $reflect = new \ReflectionClass(__CLASS__);

        foreach ($reflect->getConstants() as $intent => $val) {
            if ($intents & $val) {
                $results[] = $intent;
            }
        }

        return $results;
    }
}
