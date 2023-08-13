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
 * Note: The const declared here are the bit position, not the bitwise value.
 *
 * @link https://discord.com/developers/docs/topics/permissions
 *
 * @since 2.1.3 Namespace moved from Guild to Permissions
 * @since 2.0.0
 *
 * @property int|string $bitwise                  Bitwise value of the enabled/disabled permissions
 * @property bool       $create_instant_invite    Allows creation of instant invites
 * @property bool       $manage_channels          Allows management and editing of channels
 * @property bool       $add_reactions            Allows for the addition of reactions to messages
 * @property bool       $view_channel             Allows guild members to view a channel, which includes reading messages in text channels and joining voice channels
 * @property bool       $send_messages            Allows for sending messages in a channel and creating threads in a forum (does not allow sending messages in threads)
 * @property bool       $send_tts_messages        Allows for sending of `/tts` messages
 * @property bool       $manage_messages          Allows for deletion of other users messages
 * @property bool       $embed_links              Links sent by users with this permission will be auto-embedded
 * @property bool       $attach_files             Allows for uploading images and files
 * @property bool       $read_message_history     Allows for reading of message history
 * @property bool       $mention_everyone         Allows for using the `@everyone` tag to notify all users in a channel, and the `@here` tag to notify all online users in a channel
 * @property bool       $use_external_emojis      Allows the usage of custom emojis from other servers
 * @property bool       $manage_roles             Allows management and editing of roles
 * @property bool       $manage_webhooks          Allows management and editing of webhooks
 * @property bool       $use_application_commands Allows members to use application commands, including slash commands and context menu commands.
 * @property bool       $use_external_stickers    Allows the usage of custom stickers from other servers
 * @property bool       $send_voice_messages      Allows sending voice messages
 */
abstract class Permission extends Part
{
    /**
     * Array of permissions that only apply to text channels.
     * i.e. indicated T in documentation.
     *
     * The constant values here are the bit position, not the bitwise value
     *
     * @see ChannelPermission
     *
     * @var array
     */
    public const TEXT_PERMISSIONS = [
        'manage_threads' => 34,
        'create_public_threads' => 35,
        'create_private_threads' => 36,
        'send_messages_in_threads' => 38,
    ];

    /**
     * Array of permissions that only apply to voice channels.
     * i.e. indicated V in documentation.
     *
     * @see ChannelPermission
     *
     * @var array
     */
    public const VOICE_PERMISSIONS = [
        'priority_speaker' => 8,
        'stream' => 9,
        'connect' => 20,
        'speak' => 21,
        'mute_members' => 22,
        'deafen_members' => 23,
        'move_members' => 24,
        'use_vad' => 25,
        'manage_events' => 33,
        'use_embedded_activities' => 39,
        'use_soundboard' => 42,
        'use_external_sounds' => 45,
    ];

    /**
     * Array of permissions that only apply to stage channels.
     * i.e. indicated S in documentation.
     *
     * @see ChannelPermission
     *
     * @var array
     */
    public const STAGE_PERMISSIONS = [
        'stream' => 9,
        'connect' => 20,
        'mute_members' => 22,
        'move_members' => 24,
        'request_to_speak' => 32,
        'manage_events' => 33,
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
        'add_reactions' => 6,
        'view_channel' => 10,
        'send_messages' => 11,
        'send_tts_messages' => 12,
        'manage_messages' => 13,
        'embed_links' => 14,
        'attach_files' => 15,
        'read_message_history' => 16,
        'mention_everyone' => 17,
        'use_external_emojis' => 18,
        'manage_roles' => 28,
        'manage_webhooks' => 29,
        'use_application_commands' => 31,
        'use_external_stickers' => 37,
        'send_voice_messages' => 46,
    ];

    /**
     * Array of permissions that can only be applied to roles.
     * i.e. indicated empty in documentation.
     *
     * @see RolePermission
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
        'manage_guild_expressions' => 30,
        'moderate_members' => 40,
        'view_creator_monetization_analytics' => 41,
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

    /**
     * @deprecated 10.0.0 Use `manage_guild_expressions`
     */
    protected function getManageEmojisAndStickersAttribute()
    {
        return $this->attributes['manage_guild_expressions'] ?? null;
    }

    /**
     * @deprecated 10.0.0 Use `manage_guild_expressions`
     */
    protected function setManageEmojisAndStickersAttribute(bool $value): void
    {
        $this->attributes['manage_guild_expressions'] = $value;
    }

    public function __toString(): string
    {
        return (string) $this->bitwise;
    }
}
