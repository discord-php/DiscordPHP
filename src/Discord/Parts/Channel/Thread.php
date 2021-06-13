<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Carbon\Carbon;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Represents a Discord thread.
 *
 * @property string       $id                    The ID of the thread.
 * @property string       $guild_id              The ID of the guild which the thread belongs to.
 * @property string       $name                  The name of the thread.
 * @property string       $last_message_id       The ID of the last message sent in the thread.
 * @property Carbon|null  $last_pin_timestamp    The timestamp when the last message was pinned in the thread.
 * @property int          $rate_limit_per_user   Amount of seconds a user has to wait before sending a new message.
 * @property string       $owner_id              The ID of the owner of the thread.
 * @property string       $parent_id             The ID of the channel which the thread was started in.
 * @property int          $message_count         An approximate count of the number of messages sent in the thread. Stops counting at 50.
 * @property int          $member_count          An approximate count of the number of members in the thread. Stops counting at 50.
 * @property Guild|null   $guild                 The guild which the thread belongs to.
 * @property User|null    $owner                 The owner of the thread.
 * @property Member|null  $member                The member object for the owner of the thread.
 * @property Channel|null $parent                The channel which the thread was created in.
 * @property bool         $archived              Whether the thread has been archived.
 * @property bool         $locked                Whether the thread has been locked.
 * @property int          $auto_archive_duration The number of minutes of inactivity until the thread is automatically archived.
 * @property string|null  $archiver_id           The ID of the user that archived the thread, if any.
 * @property User|null    $archiver              The user that archived the thread, if any.
 * @property Member|null  $archiver_member       The corresponding member object for the user that archived the thread, if any.
 * @property Carbon       $archive_timestamp     The time that the thread's archive status was changed.
 */
class Thread extends Part
{
    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'guild_id',
        'name',
        'last_message_id',
        'last_pin_timestamp',
        'rate_limit_per_user',
        'owner_id',
        'parent_id',
        'message_count',
        'member_count',
        'thread_metadata',
    ];

    protected $visible = [
        'guild',
        'owner',
        'member',
        'parent',
        'archived',
        'locked',
        'auto_archive_duration',
        'archiver_id',
        'archiver',
        'archiver_member',
        'archive_timestamp',
    ];

    /**
     * Returns the guild which the thread belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the owner of the thread.
     *
     * @return User|null
     */
    protected function getOwnerAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->owner_id);
    }

    /**
     * Returns the member object for the owner of the thread.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($this->guild) {
            return $this->guild->members->get('id', $this->owner_id);
        }

        return null;
    }

    /**
     * Returns the parent channel of the thread.
     *
     * @return Channel|null
     */
    protected function getParentAttribute(): ?Channel
    {
        if ($this->guild) {
            return $this->guild->channels->get('id', $this->parent_id);
        }

        return $this->discord->getChannel($this->parent_id);
    }

    /**
     * Returns the timestamp when the last message was pinned in the thread.
     *
     * @return Carbon|null
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        if (isset($this->attributes['last_pin_timestamp'])) {
            return new Carbon($this->attributes['last_pin_timestamp']);
        }

        return null;
    }

    /**
     * Returns whether the thread is archived.
     *
     * @return bool
     */
    protected function getArchivedAttribute(): bool
    {
        return $this->thread_metadata->archived;
    }

    /**
     * Returns whether the thread has been locked.
     *
     * @return bool
     */
    protected function getLockedAttribute(): bool
    {
        return $this->thread_metadata->locked ?? false;
    }

    /**
     * Returns the number of minutes of inactivity required for the thread
     * to auto archive.
     *
     * @return int
     */
    protected function getAutoArchiveDurationAttribute(): int
    {
        return $this->thread_metadata->auto_archive_duration;
    }

    /**
     * Returns the ID of the user who archived the thread.
     *
     * @return string|null
     */
    protected function getArchiverIdAttribute(): ?string
    {
        return $this->thread_metadata->archiver_id ?? null;
    }

    /**
     * Returns the user who archived the thread.
     *
     * @return User|null
     */
    protected function getArchiverAttribute(): ?User
    {
        if ($this->archiver_id) {
            return $this->discord->users->get('id', $this->archiver_id);
        }

        return null;
    }

    /**
     * Returns the member object for the user who archived the thread.
     *
     * @return Member|null
     */
    protected function getArchiverMemberAttribute(): ?Member
    {
        if ($this->archiver_id && $this->guild) {
            return $this->guild->members->get('id', $this->archiver_id);
        }

        return null;
    }

    /**
     * Returns the time that the thread's archive status was changed.
     *
     * Note that this does not mean the time that the thread was archived - it can
     * also mean the time when the thread was created, archived, unarchived etc.
     *
     * @return Carbon
     */
    protected function getArchiveTimestampAttribute(): Carbon
    {
        return new Carbon($this->thread_metadata->archive_timestamp);
    }
}
