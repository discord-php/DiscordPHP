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

use Discord\Discord;
use Discord\Parts\Part;

/**
 * Permission represents a set of permissions for a given role or overwrite.
 *
 * @property int  $bitwise
 * @property bool $create_instant_invite
 * @property bool $manage_channels
 * @property bool $view_channel
 * @property bool $manage_roles
 * @property bool $manage_webhooks
 */
abstract class Permission extends Part
{
    /**
     * Array of permissions that only apply to voice channels.
     *
     * @var array
     */
    public const VOICE_PERMISSIONS = [
        'priority_speaker' => (1 << 8),
        'stream' => (1 << 9),
        'connect' => (1 << 20),
        'speak' => (1 << 21),
        'mute_members' => (1 << 22),
        'deafen_members' => (1 << 23),
        'move_members' => (1 << 24),
        'use_vad' => (1 << 25),
        'request_to_speak' => (1 << 32),
        'manage_events' => (1 << 33)
        'start_embedded_activities' => (1 << 39),
    ];

    /**
     * Array of permissions that only apply to text channels.
     *
     * @var array
     */
    public const TEXT_PERMISSIONS = [
        'add_reactions' => (1 << 6),
        'send_messages' => (1 << 11),
        'send_tts_messages' => (1 << 12),
        'manage_messages' => (1 << 13),
        'embed_links' => (1 << 14),
        'attach_files' => (1 << 15),
        'read_message_history' => (1 << 16),
        'mention_everyone' => (1 << 17),
        'use_external_emojis' => (1 << 18),
        'use_slash_commands' => (1 << 31),
        'manage_threads' => (1 << 34),
        'use_public_threads' => (1 << 35),
        'use_private_threads' => (1 << 36),
        'use_external_stickers' => (1 << 37),
        'send_messages_in_threads' => (1 << 38),
    ];

    /**
     * Array of permissions that can only be applied to roles.
     *
     * @var array
     */
    public const ROLE_PERMISSIONS = [
        'kick_members' => (1 << 1),
        'ban_members' => (1 << 2),
        'administrator' => (1 << 3),
        'manage_guild' => (1 << 5),
        'view_audit_log' => (1 << 7),
        'view_guild_insights' => (1 << 19),
        'change_nickname' => (1 << 26),
        'manage_nicknames' => (1 << 27),
        'manage_emojis' => (1 << 30),
    ];

    /**
     * Array of permissions for all roles.
     *
     * @var array
     */
    public const ALL_PERMISSIONS = [
        'create_instant_invite' => (1 << 0),
        'manage_channels' => (1 << 4),
        'view_channel' => (1 << 10),
        'manage_roles' => (1 << 28),
        'manage_webhooks' => (1 << 29),
    ];

    /**
     * Array of permissions.
     *
     * @var array
     */
    private $permissions = [];

    /**
     * @inheritdoc
     */
    public function __construct(Discord $discord, array $attributes = [], bool $created = false)
    {
        $this->permissions = $this->getPermissions();
        $this->fillable = array_keys($this->permissions);
        $this->fillable[] = 'bitwise';

        parent::__construct($discord, $attributes, $created);

        foreach ($this->fillable as $permission) {
            if (! isset($this->attributes[$permission])) {
                $this->attributes[$permission] = false;
            }
        }
    }

    /**
     * Returns an array of extra permissions.
     *
     * @return array
     */
    abstract public static function getPermissions(): array;

    /**
     * Gets the bitwise attribute of the permission.
     *
     * @return int
     */
    protected function getBitwiseAttribute(): int
    {
        $bitwise = 0;

        foreach ($this->permissions as $permission => $value) {
            if ($this->attributes[$permission]) {
                $bitwise |= $value;
            }
        }

        return $bitwise;
    }

    /**
     * Sets the bitwise attribute of the permission.
     *
     * @param int|string $bitwise
     */
    protected function setBitwiseAttribute($bitwise)
    {
        if (is_string($bitwise)) {
            $bitwise = (int) $bitwise;
        }

        foreach ($this->permissions as $permission => $value) {
            if (($bitwise & $value) == $value) {
                $this->attributes[$permission] = true;
            } else {
                $this->attributes[$permission] = false;
            }
        }
    }
}
