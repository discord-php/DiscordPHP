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
 */
class ChannelPermission extends Permission
{
    /**
     * {@inheritdoc}
     */
    protected $default = 0;

    /**
     * {@inheritdoc}
     */
    protected $bitoffset = [
        'create_instant_invite' => 0,
        'manage_permissions'    => 3,
        'manage_channel'        => 4,

        'read_messages'        => 10,
        'send_messages'        => 11,
        'send_tts_messages'    => 12,
        'manage_messages'      => 13,
        'embed_links'          => 14,
        'attach_files'         => 15,
        'read_message_history' => 16,
        'mention_everyone'     => 17,
    ];
}
