<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Permissions;

/**
 * {@inheritdoc}
 *
 * @property bool $create_instant_invite
 * @property bool $kick_members
 * @property bool $ban_members
 * @property bool $administrator
 * @property bool $manage_channels
 * @property bool $manage_server
 * @property bool $view_audit_log
 * @property bool $change_nickname
 * @property bool $manage_nicknames
 * @property bool $manage_roles
 * @property bool $manage_webhooks
 * @property bool $manage_emojis
 * @property bool $read_messages
 * @property bool $send_messages
 * @property bool $send_tts_messages
 * @property bool $manage_messages
 * @property bool $embed_links
 * @property bool $attach_files
 * @property bool $read_message_history
 * @property bool $mention_everyone
 * @property bool $use_external_emojis
 * @property bool $add_reactions
 * @property bool $voice_connect
 * @property bool $voice_speak
 * @property bool $voice_mute_members
 * @property bool $voice_deafen_members
 * @property bool $voice_move_members
 * @property bool $voice_use_vad
 */
class RolePermission extends Permission
{
    /**
     * {@inheritdoc}
     */
    protected $bitwise = [
        'create_instant_invite' => 0,
        'kick_members'          => 1,
        'ban_members'           => 2,
        'administrator'         => 3,
        'manage_channels'       => 4,
        'manage_server'         => 5,
        'view_audit_log'        => 7,
        'change_nickname'       => 26,
        'manage_nicknames'      => 27,
        'manage_roles'          => 28,
        'manage_webhooks'       => 29,
        'manage_emojis'         => 30,

        'read_messages'        => 10,
        'send_messages'        => 11,
        'send_tts_messages'    => 12,
        'manage_messages'      => 13,
        'embed_links'          => 14,
        'attach_files'         => 15,
        'read_message_history' => 16,
        'mention_everyone'     => 17,
        'use_external_emojis'  => 18,
        'add_reactions'        => 6,

        'voice_connect'        => 20,
        'voice_speak'          => 21,
        'voice_mute_members'   => 22,
        'voice_deafen_members' => 23,
        'voice_move_members'   => 24,
        'voice_use_vad'        => 25,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return [
            'create_instant_invite' => true,

            'read_messages'        => true,
            'send_messages'        => true,
            'send_tts_messages'    => true,
            'embed_links'          => true,
            'attach_files'         => true,
            'read_message_history' => true,
            'mention_everyone'     => true,

            'voice_connect' => true,
            'voice_speak'   => true,
            'voice_use_vad' => true,
        ];
    }
}
