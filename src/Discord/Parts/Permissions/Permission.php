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
use Discord\Helpers\Bitwise;
use Discord\Parts\Part;

/**
 * Permission represents a set of permissions for a given role or overwrite.
 *
 * @see https://discord.com/developers/docs/topics/permissions
 *
 * @property int|string $bitwise
 * @property bool       $create_instant_invite
 * @property bool       $manage_channels
 * @property bool       $view_channel
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
        'priority_speaker' => 8,
        'stream' => 9,
        'connect' => 20,
        'speak' => 21,
        'mute_members' => 22,
        'deafen_members' => 23,
        'move_members' => 24,
        'use_vad' => 25,
        'manage_events' => 33,
        'start_embedded_activities' => 39, // @todo use_embedded_activities
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
        'mention_everyone' => 17,
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
        'manage_roles' => 28,
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
     * @return int|string
     */
    protected function getBitwiseAttribute()
    {
        if (Bitwise::is32BitWithGMP()) { // x86 with GMP
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
     * @param int|string $bitwise
     */
    protected function setBitwiseAttribute($bitwise)
    {
        if (PHP_INT_SIZE === 8 && is_string($bitwise)) { // x64
            $bitwise = (int) $bitwise;
        }

        foreach ($this->permissions as $permission => $value) {
            if (Bitwise::test($bitwise, $value)) {
                $this->attributes[$permission] = true;
            } else {
                $this->attributes[$permission] = false;
            }
        }
    }

    /**
     * @inheritdoc
     *
     * @todo replace start_embedded_activities in next major version
     */
    protected function getUseEmbeddedActivitiesAttribute()
    {
        return $this->attributes['start_embedded_activities'] ?? null;
    }

    /**
     * @inheritdoc
     *
     * @todo replace start_embedded_activities in next major version
     */
    protected function setUseEmbeddedActivitiesAttribute($value)
    {
        $this->attributes['start_embedded_activities'] = $value;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `use_application_commands`
     */
    protected function getUseSlashCommandsAttribute()
    {
        return $this->attributes['use_application_commands'] ?? null;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `create_public_threads`
     */
    protected function getUsePublicThreadsAttribute()
    {
        return $this->attributes['create_public_threads'] ?? null;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `create_private_threads`
     */
    protected function getUsePrivateThreadsAttribute()
    {
        return $this->attributes['create_private_threads'] ?? null;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `manage_emojis_and_stickers`
     */
    protected function getManageEmojisAttribute()
    {
        return $this->attributes['manage_emojis_and_stickers'] ?? null;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `use_application_commands`
     */
    protected function setUseSlashCommandsAttribute($value)
    {
        return $this->attributes['use_application_commands'] = $value;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `create_public_threads`
     */
    protected function setUsePublicThreadsAttribute($value)
    {
        return $this->attributes['create_public_threads'] = $value;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `create_private_threads`
     */
    protected function setUsePrivateThreadsAttribute($value)
    {
        return $this->attributes['create_private_threads'] = $value;
    }

    /**
     * @inheritdoc
     *
     * @deprecated 7.0.0 Use `manage_emojis_and_stickers`
     */
    protected function setManageEmojisAttribute($value)
    {
        return $this->attributes['manage_emojis_and_stickers'] = $value;
    }
}
