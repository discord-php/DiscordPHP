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
 * @property bool $add_reactions
 * @property bool $priority_speaker
 * @property bool $stream
 * @property bool $send_messages
 * @property bool $send_tts_messages
 * @property bool $manage_messages
 * @property bool $embed_links
 * @property bool $attach_files
 * @property bool $read_message_history
 * @property bool $use_external_emojis
 * @property bool $connect
 * @property bool $speak
 * @property bool $mute_members
 * @property bool $deafen_members
 * @property bool $move_members
 * @property bool $use_vad
 * @property bool $manage_webhooks
 * @property bool $use_application_commands
 * @property bool $request_to_speak
 * @property bool $manage_events
 * @property bool $manage_threads
 * @property bool $create_public_threads
 * @property bool $create_private_threads
 * @property bool $use_external_stickers
 * @property bool $send_messages_in_threads
 * @property bool $use_embedded_activities
 *
 * @property bool $kick_members
 * @property bool $ban_members
 * @property bool $administrator
 * @property bool $manage_guild
 * @property bool $view_audit_log
 * @property bool $view_guild_insights
 * @property bool $change_nickname
 * @property bool $manage_nicknames
 * @property bool $manage_guild_expressions
 * @property bool $moderate_members
 * @property bool $view_creator_monetization_analytics
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
