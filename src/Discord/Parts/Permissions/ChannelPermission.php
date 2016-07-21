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
 * @property bool $manage_channels
 * @property bool $manage_permissions
 * @property bool $read_messages
 * @property bool $send_messages
 * @property bool $send_tts_messages
 * @property bool $manage_messages
 * @property bool $embed_links
 * @property bool $attach_files
 * @property bool $read_message_history
 * @property bool $mention_everyone
 * @property bool $voice_connect
 * @property bool $voice_speak
 * @property bool $voice_mute_members
 * @property bool $voice_deafen_members
 * @property bool $voice_move_members
 * @property bool $voice_use_vad
 */
class ChannelPermission extends Permission
{
    /**
     * {@inheritdoc}
     */
    protected $bitwise = [
        'create_instant_invite' => 0,
        'manage_channels'       => 4,
        'manage_permissions'    => 28,

        'read_messages'        => 10,
        'send_messages'        => 11,
        'send_tts_messages'    => 12,
        'manage_messages'      => 13,
        'embed_links'          => 14,
        'attach_files'         => 15,
        'read_message_history' => 17,
        'mention_everyone'     => 18,

        'voice_connect'        => 20,
        'voice_speak'          => 21,
        'voice_mute_members'   => 22,
        'voice_deafen_members' => 23,
        'voice_move_members'   => 24,
        'voice_use_vad'        => 25,
    ];

    /**
     * {@inheritdoc}
     *
     * @param int $deny The deny bitwise integer.
     *
     * @return this
     */
    public function decodeBitwise($bitwise, $deny = 0)
    {
        $result = $this->getDefault();

        foreach ($this->bitwise as $key => $value) {
            if (true === ((($bitwise >> $value) & 1) == 1)) {
                $result[$key] = true;
            } elseif (true === ((($deny >> $value) & 1) == 1)) {
                $result[$key] = false;
            }
        }

        $this->fill($result);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return array Bitwise.
     */
    public function getBitwiseAttribute()
    {
        $allow = 0;
        $deny  = 0;

        foreach ($this->attributes as $key => $value) {
            if (true === $value) {
                $allow |= (1 << $this->bitwise[$key]);
            } elseif (false === $value) {
                $deny |= (1 << $this->bitwise[$key]);
            }
        }

        return [$allow, $deny];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        $default = [];

        foreach ($this->bitwise as $key => $bit) {
            $default[$key] = null;
        }

        return $default;
    }
}
