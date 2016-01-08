<?php

namespace Discord\Parts\Permissions;

use Discord\Parts\Permissions\Permission;

class RolePermission extends Permission
{
    /**
     * The default permissions.
     *
     * @var integer 
     */
    protected $default = 36953089;

    /**
     * The Bit Offset map.
     *
     * @var array 
     */
    protected $bitoffset = [
        'create_instant_invite' => 0,
        'kick_members'          => 1,
        'ban_members'           => 2,
        'manage_roles'          => 3,
        'manage_channels'       => 4,
        'manage_server'         => 5,

        'read_messages'         => 10,
        'send_messages'         => 11,
        'send_tts_messages'     => 12,
        'manage_messages'       => 13,
        'embed_links'           => 14,
        'attach_files'          => 15,
        'read_message_history'  => 16,
        'mention_everyone'      => 17,

        'voice_connect'         => 20,
        'voice_speak'           => 21,
        'voice_mute_members'    => 22,
        'voice_deafen_members'  => 23,
        'voice_move_members'    => 24,
        'voice_use_vad'         => 25
    ];
}
