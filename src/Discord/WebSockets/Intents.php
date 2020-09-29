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
     */
    const GUILDS = (1 << 0);

    /**
     * Guild member events:.
     *
     * - GUILD_MEMBER_ADD
     * - GUILD_MEMBER_UPDATE
     * - GUILD_MEMBER_REMOVE
     */
    const GUILD_MEMBERS = (1 << 1);

    /**
     * Guild ban events:.
     *
     * - GUILD_BAN_ADD
     * - GUILD_BAN_REMOVE
     */
    const GUILD_BANS = (1 << 2);

    /**
     * Guild emoji events:.
     *
     * - GUILD_EMOJIS_UPDATE
     */
    const GUILD_EMOJIS = (1 << 3);

    /**
     * Guild integration events:.
     *
     * - GUILD_INTEGRATIONS_UPDATE
     */
    const GUILD_INTEGRATIONS = (1 << 4);

    /**
     * Guild webhook events.
     *
     * - WEBHOOKS_UPDATE
     */
    const GUILD_WEBHOOKS = (1 << 5);

    /**
     * Guild invite events:.
     *
     * - INVITE_CREATE
     * - INVITE_DELETE
     */
    const GUILD_INVITES = (1 << 6);

    /**
     * Guild voice state events:.
     *
     * - VOICE_STATE_UPDATE
     */
    const GUILD_VOICE_STATES = (1 << 7);

    /**
     * Guild presence events:.
     *
     * - PRESENECE_UPDATE
     */
    const GUILD_PRESENCES = (1 << 8);

    /**
     * Guild message events:.
     *
     * - MESSAGE_CREATE
     * - MESSAGE_UPDATE
     * - MESSAGE_DELETE
     * - MESSAGE_DELETE_BULK
     */
    const GUILD_MESSAGES = (1 << 9);

    /**
     * Guild message reaction events:.
     *
     * - MESSAGE_REACTION_ADD
     * - MESSAGE_REACTION_REMOVE
     * - MESSAGE_REACTION_REMOVE_ALL
     * - MESSAGE_REACTION_REMOVE_EMOJI
     */
    const GUILD_MESSAGE_REACTIONS = (1 << 10);

    /**
     * Guild typing events:.
     *
     * - TYPING_START
     */
    const GUILD_MESSAGE_TYPING = (1 << 11);

    /**
     * Direct message events:.
     *
     * - CHANNEL_CREATE
     * - MESSAGE_CREATE
     * - MESSAGE_UPDATE
     * - MESSAGE_DELETE
     * - CHANNEL_PINS_UPDATE
     */
    const DIRECT_MESSAGES = (1 << 12);

    /**
     * Direct message reaction events:.
     *
     * - MESSAGE_REACTION_ADD
     * - MESSAGE_REACTION_REMOVE
     * - MESSAGE_REACTION_REMOVE_ALL
     * - MESSAGE_REACTION_REMOVE_EMOJI
     */
    const DIRECT_MESSAGE_REACTIONS = (1 << 13);

    /**
     * Direct message typing events:.
     *
     * - TYPING_START
     */
    const DIRECT_MESSAGE_TYPING = (1 << 14);

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
}
