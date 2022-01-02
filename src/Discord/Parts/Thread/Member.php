<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Thread;

use Carbon\Carbon;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * Represents a member that belongs to a thread. Not the same as a user nor a guild member.
 *
 * @property string $id             ID of the thread.
 * @property string $user_id        ID of the user that the member object represents.
 * @property User   $user           The user that the member object represents.
 * @property int    $flags          Flags relating to the member. Only used for client notifications.
 * @property Carbon $join_timestamp The time that the member joined the thread.
 */
class Member extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'user_id', 'flags', 'join_timestamp'];

    /**
     * Returns the time that the member joined the thread.
     *
     * @return Carbon
     */
    protected function getJoinTimestampAttribute(): Carbon
    {
        return new Carbon($this->attributes['join_timestamp']);
    }

    /**
     * Returns the user that the member represents.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Attempts to remove the member from the thread.
     *
     * @return ExtendedPromiseInterface
     */
    public function remove(): ExtendedPromiseInterface
    {
        return $this->http->delete(Endpoint::bind(Endpoint::THREAD_MEMBER, $this->id, $this->user_id));
    }
}
