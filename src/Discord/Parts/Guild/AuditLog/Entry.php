<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild\AuditLog;

use Discord\Helpers\Collection;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents an entry in the audit log.
 *
 * @see https://discord.com/developers/docs/resources/audit-log#audit-log-entry-object
 *
 * @property string       $target_id   Id of the affected entity (webhook, user, role, etc.).
 * @property Collection   $changes     Changes made to the target_id.
 * @property string|null  $user_id     The user who made the changes.
 * @property User|null    $user
 * @property string       $id          Id of the entry.
 * @property int          $action_type Type of action that occurred.
 * @property Options|null $options     Additional info for certain action types.
 * @property string|null  $reason      The reason for the change (0-512 characters).
 */
class Entry extends Part
{
    // AUDIT LOG ENTRY TYPES
    public const GUILD_UPDATE = 1;
    public const CHANNEL_CREATE = 10;
    public const CHANNEL_UPDATE = 11;
    public const CHANNEL_DELETE = 12;
    public const CHANNEL_OVERWRITE_CREATE = 13;
    public const CHANNEL_OVERWRITE_UPDATE = 14;
    public const CHANNEL_OVERWRITE_DELETE = 15;
    public const MEMBER_KICK = 20;
    public const MEMBER_PRUNE = 21;
    public const MEMBER_BAN_ADD = 22;
    public const MEMBER_BAN_REMOVE = 23;
    public const MEMBER_UPDATE = 24;
    public const MEMBER_ROLE_UPDATE = 25;
    public const MEMBER_MOVE = 26;
    public const MEMBER_DISCONNECT = 27;
    public const BOT_ADD = 28;
    public const ROLE_CREATE = 30;
    public const ROLE_UPDATE = 31;
    public const ROLE_DELETE = 32;
    public const INVITE_CREATE = 40;
    public const INVITE_UPDATE = 41;
    public const INVITE_DELETE = 42;
    public const WEBHOOK_CREATE = 50;
    public const WEBHOOK_UPDATE = 51;
    public const WEBHOOK_DELETE = 52;
    public const EMOJI_CREATE = 60;
    public const EMOJI_UPDATE = 61;
    public const EMOJI_DELETE = 62;
    public const MESSAGE_DELETE = 72;
    public const MESSAGE_BULK_DELETE = 63;
    public const MESSAGE_PIN = 74;
    public const MESSAGE_UNPIN = 75;
    public const INTEGRATION_CREATE = 80;
    public const INTEGRATION_UPDATE = 81;
    public const INTEGRATION_DELETE = 82;
    public const STAGE_INSTANCE_CREATE = 83;
    public const STAGE_INSTANCE_UPDATE = 84;
    public const STAGE_INSTANCE_DELETE = 85;
    public const STICKER_CREATE = 90;
    public const STICKER_UPDATE = 91;
    public const STICKER_DELETE = 92;
    public const GUILD_SCHEDULED_EVENT_CREATE = 100;
    public const GUILD_SCHEDULED_EVENT_UPDATE = 101;
    public const GUILD_SCHEDULED_EVENT_DELETE = 102;
    public const THREAD_CREATE = 110;
    public const THREAD_UPDATE = 111;
    public const THREAD_DELETE = 112;
    public const APPLICATION_COMMAND_PERMISSION_UPDATE = 121;
    public const AUTO_MODERATION_RULE_CREATE = 140;
    public const AUTO_MODERATION_RULE_UPDATE = 141;
    public const AUTO_MODERATION_RULE_DELETE = 142;
    public const AUTO_MODERATION_BLOCK_MESSAGE = 143;

    // AUDIT LOG ENTRY TYPES

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'target_id',
        'changes',
        'user_id',
        'id',
        'action_type',
        'options',
        'reason',
    ];

    /**
     * Returns the user who made the changes.
     *
     * @return User
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Returns a collection of changes.
     *
     * @see https://discord.com/developers/docs/resources/audit-log#audit-log-change-object
     *
     * @return Collection
     */
    protected function getChangesAttribute(): Collection
    {
        return new Collection($this->attributes['changes'] ?? [], 'key', null);
    }

    /**
     * Returns the options of the entry.
     *
     * @return Options
     */
    protected function getOptionsAttribute(): Options
    {
        return $this->factory->create(Options::class, $this->attributes['options'] ?? [], true);
    }
}
