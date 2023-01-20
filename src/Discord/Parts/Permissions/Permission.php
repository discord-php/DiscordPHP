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
use Discord\Helpers\BigInt;
use Discord\Parts\Part;

/**
 * Permission represents a set of permissions for a given role or overwrite.
 *
 * @link https://discord.com/developers/docs/topics/permissions
 *
 * @since 2.1.3 Namespace moved from Guild to Permissions
 * @since 2.0.0
 *
 * @property int|string $bitwise
 * @property bool       $create_instant_invite
 * @property bool       $manage_channels
 * @property bool       $view_channel
 * @property bool       $mention_everyone
 * @property bool       $manage_roles
 */
abstract class Permission extends Part
{
    /**
     * Array of permissions that only apply to stage channels.
     * i.e. indicated S in documentation.
     *
     * @var array
     */
    public const STAGE_PERMISSIONS = [
        'connect' => 20,
        'mute_members' => 22,
        'deafen_members' => 23,
        'move_members' => 24,
        'request_to_speak' => 32,
        'manage_events' => 33,
    ];

    /**
     * Array of permissions that only apply to voice channels.
     * i.e. indicated V in documentation.
     *
     * @var array
     */
    public const VOICE_PERMISSIONS = [
        'add_reactions' => 6,
        'priority_speaker' => 8,
        'stream' => 9,
        'send_messages' => 11,
        'send_tts_messages' => 12,
        'manage_messages' => 13,
        'embed_links' => 14,
        'attach_files' => 15,
        'read_message_history' => 16,
        'use_external_emojis' => 18,
        'connect' => 20,
        'speak' => 21,
        'mute_members' => 22,
        'deafen_members' => 23,
        'move_members' => 24,
        'use_vad' => 25,
        'manage_webhooks' => 29,
        'manage_events' => 33,
        'use_external_stickers' => 37,
        'use_embedded_activities' => 39,
    ];

    /**
     * Array of permissions that only apply to text channels.
     * i.e. indicated T in documentation.
     *
     * @var array
     */
    public const TEXT_PERMISSIONS = [
        'add_reactions' => 6,
        'send_messages' => 11,
        'send_tts_messages' => 12,
        'manage_messages' => 13,
        'embed_links' => 14,
        'attach_files' => 15,
        'read_message_history' => 16,
        'use_external_emojis' => 18,
        'manage_webhooks' => 29,
        'use_application_commands' => 31,
        'manage_threads' => 34,
        'create_public_threads' => 35,
        'create_private_threads' => 36,
        'use_external_stickers' => 37,
        'send_messages_in_threads' => 38,
    ];

    /**
     * Array of permissions that can only be applied to roles.
     * i.e. indicated empty in documentation.
     *
     * @var array
     */
    public const ROLE_PERMISSIONS = [
        'kick_members' => 1,
        'ban_members' => 2,
        'administrator' => 3,
        'manage_guild' => 5,
        'view_audit_log' => 7,
        'view_guild_insights' => 19,
        'change_nickname' => 26,
        'manage_nicknames' => 27,
        'manage_emojis_and_stickers' => 30,
        'moderate_members' => 40,
        'view_creator_monetization_analytics' => 41,
    ];

    /**
     * Array of permissions for all roles.
     * i.e. indicated T,V,S in documentation.
     *
     * @var array
     */
    public const ALL_PERMISSIONS = [
        'create_instant_invite' => 0,
        'manage_channels' => 4,
        'view_channel' => 10,
        'mention_everyone' => 17,
        'manage_roles' => 28,
    ];

    /**
     * Array of permissions.
     *
     * @var array
     */
    private $permissions = [];

    /**
     * {@inheritDoc}
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
     * @link https://discord.com/developers/docs/topics/permissions#permissions-bitwise-permission-flags
     *
     * @return int|string
     */
    protected function getBitwiseAttribute()
    {
        if (BigInt::is32BitWithGMP()) { // x86 with GMP
            $bitwise = \gmp_init(0);

            foreach ($this->permissions as $permission => $value) {
                \gmp_setbit($bitwise, $value, $this->attributes[$permission]);
            }

            return \gmp_strval($bitwise);
        }

        $bitwise = 0;

        foreach ($this->permissions as $permission => $value) {
            if ($this->attributes[$permission]) {
                $bitwise |= 1 << $value;
            }
        }

        return $bitwise;
    }

    /**
     * Sets the bitwise attribute of the permission.
     *
     * @link https://discord.com/developers/docs/topics/permissions#permissions-bitwise-permission-flags
     *
     * @param int|string $bitwise
     */
    protected function setBitwiseAttribute($bitwise)
    {
        if (PHP_INT_SIZE === 8 && is_string($bitwise)) { // x64
            $bitwise = (int) $bitwise;
        }

        foreach ($this->permissions as $permission => $value) {
            $this->attributes[$permission] = BigInt::test($bitwise, $value);
        }
    }

    public function __toString(): string
    {
        return (string) $this->bitwise;
    }
}
