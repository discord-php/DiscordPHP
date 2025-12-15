<?php

declare(strict_types=1);

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
 * @since 10.19.0 Added constants for all permissions.
 * @since 2.1.3 Namespace moved from Guild to Permissions
 * @since 2.0.0
 *
 * @property int|string $bitwise                             Bitwise value of the enabled/disabled permissions
 * @property bool       $create_instant_invite               Allows creation of instant invites
 * @property bool       $kick_members                        Allows kicking members
 * @property bool       $ban_members                         Allows banning members
 * @property bool       $administrator                       Allows all permissions and bypasses channel permission overwrites
 * @property bool       $manage_channels                     Allows management and editing of channels
 * @property bool       $manage_guild                        Allows management and editing of the guild
 * @property bool       $add_reactions                       Allows for adding new reactions to messages
 * @property bool       $view_audit_log                      Allows for viewing of audit logs
 * @property bool       $priority_speaker                    Allows for using priority speaker in a voice channel
 * @property bool       $stream                              Allows the user to go live
 * @property bool       $view_channel                        Allows guild members to view a channel, which includes reading messages in text channels and joining voice channels
 * @property bool       $send_messages                       Allows for sending messages in a channel and creating threads in a forum (does not allow sending messages in threads)
 * @property bool       $send_tts_messages                   Allows for sending of `/tts` messages
 * @property bool       $manage_messages                     Allows for deletion of other users messages
 * @property bool       $embed_links                         Links sent by users with this permission will be auto-embedded
 * @property bool       $attach_files                        Allows for uploading images and files
 * @property bool       $read_message_history                Allows for reading of message history
 * @property bool       $mention_everyone                    Allows for using the `@everyone` tag to notify all users in a channel, and the `@here` tag to notify all online users in a channel
 * @property bool       $use_external_emojis                 Allows the usage of custom emojis from other servers
 * @property bool       $view_guild_insights                 Allows for viewing guild insights
 * @property bool       $connect                             Allows for joining of a voice channel
 * @property bool       $speak                               Allows for speaking in a voice channel
 * @property bool       $mute_members                        Allows for muting members in a voice channel
 * @property bool       $deafen_members                      Allows for deafening of members in a voice channel
 * @property bool       $move_members                        Allows for moving of members between voice channels
 * @property bool       $use_vad                             Allows for using voice-activity-detection in a voice channel
 * @property bool       $change_nickname                     Allows for modification of own nickname
 * @property bool       $manage_nicknames                    Allows for modification of other users nicknames
 * @property bool       $manage_roles                        Allows management and editing of roles
 * @property bool       $manage_webhooks                     Allows management and editing of webhooks
 * @property bool       $manage_guild_expressions            Allows for editing and deleting emojis, stickers, and soundboard sounds created by all users
 * @property bool       $use_application_commands            Allows members to use application commands, including slash commands and context menu commands
 * @property bool       $request_to_speak                    Allows for requesting to speak in stage channels
 * @property bool       $manage_events                       Allows for editing and deleting scheduled events created by all users
 * @property bool       $manage_threads                      Allows for deleting and archiving threads, and viewing all private threads
 * @property bool       $create_public_threads               Allows for creating public and announcement threads
 * @property bool       $create_private_threads              Allows for creating private threads
 * @property bool       $use_external_stickers               Allows the usage of custom stickers from other servers
 * @property bool       $send_messages_in_threads            Allows for sending messages in threads
 * @property bool       $use_embedded_activities             Allows for using Activities (applications with the EMBEDDED flag)
 * @property bool       $moderate_members                    Allows for timing out users to prevent them from sending or reacting to messages in chat and threads, and from speaking in voice and stage channels
 * @property bool       $view_creator_monetization_analytics Allows for viewing role subscription insights
 * @property bool       $use_soundboard                      Allows for using soundboard in a voice channel
 * @property bool       $create_guild_expressions            Allows for creating emojis, stickers, and soundboard sounds, and editing and deleting those created by the current user
 * @property bool       $create_events                       Allows for creating scheduled events, and editing and deleting those created by the current user
 * @property bool       $use_external_sounds                 Allows the usage of custom soundboard sounds from other servers
 * @property bool       $send_voice_messages                 Allows sending voice messages
 * @property bool       $send_polls                          Allows sending polls
 * @property bool       $use_external_apps                   Allows user-installed apps to send public responses. When disabled, users will still be allowed to use their apps but the responses will be ephemeral. This only applies to apps not also installed to the server.
 * @property bool       $pin_messages                        Allows pinning and unpinning messages
 * @property bool       $bypass_slowmode                     Allows members to send messages in this channel without being affected by slowmode
 */
abstract class Permission extends Part
{
    /** Allows creation of instant invites. */
    public const CREATE_INSTANT_INVITE = 0;
    /** Allows kicking members. */
    public const KICK_MEMBERS = 1;
    /** Allows banning members. */
    public const BAN_MEMBERS = 2;
    /** Allows all permissions and bypasses channel permission overwrites. */
    public const ADMINISTRATOR = 3;
    /** Allows management and editing of channels. */
    public const MANAGE_CHANNELS = 4;
    /** Allows management and editing of the guild. */
    public const MANAGE_GUILD = 5;
    /** Allows for adding new reactions to messages. This permission does not apply to reacting with an existing reaction on a message. */
    public const ADD_REACTIONS = 6;
    /** Allows for viewing of audit logs. */
    public const VIEW_AUDIT_LOG = 7;
    /** Allows for using priority speaker in a voice channel. */
    public const PRIORITY_SPEAKER = 8;
    /** Allows the user to go live. */
    public const STREAM = 9;
    /** Allows guild members to view a channel, which includes reading messages in text channels and joining voice channels. */
    public const VIEW_CHANNEL = 10;
    /** Allows for sending messages in a channel and creating threads in a forum (does not allow sending messages in threads). */
    public const SEND_MESSAGES = 11;
    /** Allows for sending of `/tts` messages. */
    public const SEND_TTS_MESSAGES = 12;
    /** Allows for deletion of other users messages. */
    public const MANAGE_MESSAGES = 13;
    /** Links sent by users with this permission will be auto-embedded. */
    public const EMBED_LINKS = 14;
    /** Allows for uploading images and files. */
    public const ATTACH_FILES = 15;
    /** Allows for reading of message history. */
    public const READ_MESSAGE_HISTORY = 16;
    /** Allows for using the `@everyone` tag to notify all users in a channel, and the `@here` tag to notify all online users in a channel. */
    public const MENTION_EVERYONE = 17;
    /** Allows the usage of custom emojis from other servers. */
    public const USE_EXTERNAL_EMOJIS = 18;
    /** Allows for viewing guild insights. */
    public const VIEW_GUILD_INSIGHTS = 19;
    /** Allows for joining of a voice channel. */
    public const CONNECT = 20;
    /** Allows for speaking in a voice channel. */
    public const SPEAK = 21;
    /** Allows for muting members in a voice channel. */
    public const MUTE_MEMBERS = 22;
    /** Allows for deafening of members in a voice channel. */
    public const DEAFEN_MEMBERS = 23;
    /** Allows for moving of members between voice channels. */
    public const MOVE_MEMBERS = 24;
    /** Allows for using voice-activity-detection in a voice channel. */
    public const USE_VAD = 25;
    /** Allows for modification of own nickname. */
    public const CHANGE_NICKNAME = 26;
    /** Allows for modification of other users nicknames. */
    public const MANAGE_NICKNAMES = 27;
    /** Allows management and editing of roles. */
    public const MANAGE_ROLES = 28;
    /** Allows management and editing of webhooks. */
    public const MANAGE_WEBHOOKS = 29;
    /** Allows for editing and deleting emojis, stickers, and soundboard sounds created by all users. */
    public const MANAGE_GUILD_EXPRESSIONS = 30;
    /** Allows members to use application commands, including slash commands and context menu commands. */
    public const USE_APPLICATION_COMMANDS = 31;
    /** Allows for requesting to speak in stage channels. (This permission is under active development and may be changed or removed.) */
    public const REQUEST_TO_SPEAK = 32;
    /** Allows for editing and deleting scheduled events created by all users. */
    public const MANAGE_EVENTS = 33;
    /** Allows for deleting and archiving threads, and viewing all private threads. */
    public const MANAGE_THREADS = 34;
    /** Allows for creating public and announcement threads. */
    public const CREATE_PUBLIC_THREADS = 35;
    /** Allows for creating private threads. */
    public const CREATE_PRIVATE_THREADS = 36;
    /** Allows the usage of custom stickers from other servers. */
    public const USE_EXTERNAL_STICKERS = 37;
    /** Allows for sending messages in threads. */
    public const SEND_MESSAGES_IN_THREADS = 38;
    /** Allows for using Activities (applications with the EMBEDDED flag). */
    public const USE_EMBEDDED_ACTIVITIES = 39;
    /** Allows for timing out users to prevent them from sending or reacting to messages in chat and threads, and from speaking in voice and stage channels. */
    public const MODERATE_MEMBERS = 40;
    /** Allows for viewing role subscription insights. */
    public const VIEW_CREATOR_MONETIZATION_ANALYTICS = 41;
    /** Allows for using soundboard in a voice channel. */
    public const USE_SOUNDBOARD = 42;
    /** Allows for creating emojis, stickers, and soundboard sounds, and editing and deleting those created by the current user. */
    public const CREATE_GUILD_EXPRESSIONS = 43;
    /** Allows for creating scheduled events, and editing and deleting those created by the current user. */
    public const CREATE_EVENTS = 44;
    /** Allows the usage of custom soundboard sounds from other servers. */
    public const USE_EXTERNAL_SOUNDS = 45;
    /** Allows sending voice messages. */
    public const SEND_VOICE_MESSAGES = 46;
    /** Allows sending polls. */
    public const SEND_POLLS = 49;
    /** Allows user-installed apps to send public responses. When disabled, users will still be allowed to use their apps but the responses will be ephemeral. This only applies to apps not also installed to the server. */
    public const USE_EXTERNAL_APPS = 50;
    /** Allows pinning and unpinning messages. */
    public const PIN_MESSAGES = 51;
    /** Allows bypassing slowmode restrictions. */
    public const BYPASS_SLOWMODE = 52;

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
        'manage_threads' => self::MANAGE_THREADS,
        'create_public_threads' => self::CREATE_PUBLIC_THREADS,
        'create_private_threads' => self::CREATE_PRIVATE_THREADS,
        'send_messages_in_threads' => self::SEND_MESSAGES_IN_THREADS,
        'pin_messages' => self::PIN_MESSAGES,
        'bypass_slowmode' => self::BYPASS_SLOWMODE,
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
        'priority_speaker' => self::PRIORITY_SPEAKER,
        'stream' => self::STREAM,
        'connect' => self::CONNECT,
        'speak' => self::SPEAK,
        'mute_members' => self::MUTE_MEMBERS,
        'deafen_members' => self::DEAFEN_MEMBERS,
        'move_members' => self::MOVE_MEMBERS,
        'use_vad' => self::USE_VAD,
        'manage_events' => self::MANAGE_EVENTS,
        'use_embedded_activities' => self::USE_EMBEDDED_ACTIVITIES,
        'use_soundboard' => self::USE_SOUNDBOARD,
        'create_events' => self::CREATE_EVENTS,
        'use_external_sounds' => self::USE_EXTERNAL_SOUNDS,
        'send_voice_messages' => self::SEND_VOICE_MESSAGES,
        'send_polls' => self::SEND_POLLS,
        'bypass_slowmode' => self::BYPASS_SLOWMODE,
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
        'stream' => self::STREAM,
        'connect' => self::CONNECT,
        'mute_members' => self::MUTE_MEMBERS,
        'move_members' => self::MOVE_MEMBERS,
        'request_to_speak' => self::REQUEST_TO_SPEAK,
        'manage_events' => self::MANAGE_EVENTS,
        'create_events' => self::CREATE_EVENTS,
        'bypass_slowmode' => self::BYPASS_SLOWMODE,
    ];

    /**
     * Array of permissions for all roles.
     * i.e. indicated T,V,S in documentation.
     *
     * @var array
     */
    public const ALL_PERMISSIONS = [
        'create_instant_invite' => self::CREATE_INSTANT_INVITE,
        'manage_channels' => self::MANAGE_CHANNELS,
        'add_reactions' => self::ADD_REACTIONS,
        'view_channel' => self::VIEW_CHANNEL,
        'send_messages' => self::SEND_MESSAGES,
        'send_tts_messages' => self::SEND_TTS_MESSAGES,
        'manage_messages' => self::MANAGE_MESSAGES,
        'embed_links' => self::EMBED_LINKS,
        'attach_files' => self::ATTACH_FILES,
        'read_message_history' => self::READ_MESSAGE_HISTORY,
        'mention_everyone' => self::MENTION_EVERYONE,
        'use_external_emojis' => self::USE_EXTERNAL_EMOJIS,
        'manage_roles' => self::MANAGE_ROLES,
        'manage_webhooks' => self::MANAGE_WEBHOOKS,
        'use_application_commands' => self::USE_APPLICATION_COMMANDS,
        'use_external_stickers' => self::USE_EXTERNAL_STICKERS,
        'send_voice_messages' => self::SEND_VOICE_MESSAGES,
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
        'kick_members' => self::KICK_MEMBERS,
        'ban_members' => self::BAN_MEMBERS,
        'administrator' => self::ADMINISTRATOR,
        'manage_guild' => self::MANAGE_GUILD,
        'view_audit_log' => self::VIEW_AUDIT_LOG,
        'view_guild_insights' => self::VIEW_GUILD_INSIGHTS,
        'change_nickname' => self::CHANGE_NICKNAME,
        'manage_nicknames' => self::MANAGE_NICKNAMES,
        'manage_guild_expressions' => self::MANAGE_GUILD_EXPRESSIONS,
        'moderate_members' => self::MODERATE_MEMBERS,
        'view_creator_monetization_analytics' => self::VIEW_CREATOR_MONETIZATION_ANALYTICS,
    ];

    /**
     * Array of permissions.
     *
     * @var array
     */
    protected $permissions = [];

    /**
     * @inheritDoc
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
    protected function setBitwiseAttribute($bitwise): void
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
