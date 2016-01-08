<?php

namespace Discord\Parts\Permissions;

use Discord\Parts\Permissions\Permission;

class ChannelPermission extends Permission
{
    /**
     * The default permissions.
     *
     * @var integer 
     */
    protected $default = 0;

    /**
     * The Bit Offset map.
     *
     * @var array 
     */
    protected $bitoffset = [
        'create_instant_invite' => 0,
        'manage_permissions'    => 3,
        'manage_channel'        => 4,

        'read_messages'         => 10,
        'send_messages'         => 11,
        'send_tts_messages'     => 12,
        'manage_messages'       => 13,
        'embed_links'           => 14,
        'attach_files'          => 15,
        'read_message_history'  => 16,
        'mention_everyone'      => 17
    ];
}
