<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Permissions;

/**
 * Represents a set of permissions for a given role.
 *
 * @since 2.1.3
 *
 * @property bool $kick_members                        Allows kicking members
 * @property bool $ban_members                         Allows banning members
 * @property bool $administrator                       Allows all permissions and bypasses channel permission overwrites
 * @property bool $manage_guild                        Allows management and editing of the guild
 * @property bool $view_audit_log                      Allows for viewing of audit logs
 * @property bool $view_guild_insights                 Allows for viewing guild insights
 * @property bool $change_nickname                     Allows for modification of own nickname
 * @property bool $manage_nicknames                    Allows for modification of other users nicknames
 * @property bool $manage_guild_expressions            Allows management and editing of emojis, stickers, and soundboard sounds
 * @property bool $moderate_members                    Allows for timing out users to prevent them from sending or reacting to messages in chat and threads, and from speaking in voice and stage channels
 * @property bool $view_creator_monetization_analytics Allows for viewing role subscription insights
 *
 * @property bool $priority_speaker
 * @property bool $stream
 * @property bool $connect
 * @property bool $speak
 * @property bool $mute_members
 * @property bool $deafen_members
 * @property bool $move_members
 * @property bool $use_vad
 * @property bool $request_to_speak
 * @property bool $manage_events
 * @property bool $manage_threads
 * @property bool $create_public_threads
 * @property bool $create_private_threads
 * @property bool $send_messages_in_threads
 * @property bool $use_embedded_activities
 * @property bool $use_soundboard
 * @property bool $use_external_sounds
 */
class RolePermission extends Permission
{
    /**
     * {@inheritDoc}
     */
    public static function getPermissions(): array
    {
        return array_merge(parent::ALL_PERMISSIONS, parent::TEXT_PERMISSIONS, parent::VOICE_PERMISSIONS, parent::STAGE_PERMISSIONS, parent::ROLE_PERMISSIONS);
    }
}
