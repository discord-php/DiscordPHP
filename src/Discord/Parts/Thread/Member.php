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
 * @see https://discord.com/developers/docs/resources/channel#thread-member-object
 *
 * @property string|null $id             ID of the thread.
 * @property string|null $user_id        ID of the user that the member object represents.
 * @property User|null   $user           The user that the member object represents.
 * @property Carbon      $join_timestamp The time that the member joined the thread.
 * @property int         $flags          Flags relating to the member. Only used for client notifications.
 */
class Member extends Part
{
    /**
     * @inheritdoc
     */
    protected $fillable = ['id', 'user_id', 'join_timestamp', 'flags'];

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
     * Returns the time that the member joined the thread.
     *
     * @return Carbon
     */
    protected function getJoinTimestampAttribute(): Carbon
    {
        return new Carbon($this->attributes['join_timestamp']);
    }

    /**
     * Attempts to remove the member from the thread.
     *
     * @see https://discord.com/developers/docs/resources/channel#remove-thread-member
     *
     * @return ExtendedPromiseInterface
     */
    public function remove(): ExtendedPromiseInterface
    {
        return $this->http->delete(Endpoint::bind(Endpoint::THREAD_MEMBER, $this->id, $this->user_id));
    }
}
