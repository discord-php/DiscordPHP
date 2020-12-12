<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild\AuditLog;

use Discord\Helpers\Collection;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents an entry in the audit log.
 *
 * @property string     $id
 * @property string     $user_id
 * @property User       $user
 * @property string     $target_id
 * @property int        $action_type
 * @property Collection $changes
 * @property Options    $options
 * @property string     $reason
 */
class Entry extends Part
{
    // AUDIT LOG ENTRY TYPES
    const GUILD_UPDATE = 1;
    const CHANNEL_CREATE = 10;
    const CHANNEL_UPDATE = 11;
    const CHANNEL_DELETE = 12;
    const CHANNEL_OVERWRITE_CREATE = 13;
    const CHANNEL_OVERWRITE_UPDATE = 14;
    const CHANNEL_OVERWRITE_DELETE = 15;
    const MEMBER_KICK = 20;
    const MEMBER_PRUNE = 21;
    const MEMBER_BAN_ADD = 22;
    const MEMBER_BAN_REMOVE = 23;
    const MEMBER_UPDATE = 24;
    const MEMBER_ROLE_UPDATE = 25;
    const MEMBER_MOVE = 26;
    const MEMBER_DISCONNECT = 27;
    const BOT_ADD = 28;
    const ROLE_CREATE = 30;
    const ROLE_UPDATE = 31;
    const ROLE_DELETE = 32;
    const INVITE_CREATE = 40;
    const INVITE_UPDATE = 41;
    const INVITE_DELETE = 42;
    const WEBHOOK_CREATE = 50;
    const WEBHOOK_UPDATE = 51;
    const WEBHOOK_DELETE = 52;
    const EMOJI_CREATE = 60;
    const EMOJI_UPDATE = 61;
    const EMOJI_DELETE = 62;
    const MESSAGE_DELETE = 72;
    const MESSAGE_BULK_DELETE = 63;
    const MESSAGE_PIN = 74;
    const MESSAGE_UNPIN = 75;
    const INTEGRATION_CREATE = 80;
    const INTEGRATION_UPDATE = 81;
    const INTEGRATION_DELETE = 82;
    // AUDIT LOG ENTRY TYPES

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'user_id',
        'target_id',
        'action_type',
        'changes',
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
