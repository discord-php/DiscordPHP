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
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member as GuildMember;
use Discord\Parts\User\User;
use React\Promise\ExtendedPromiseInterface;

/**
 * Represents a member that belongs to a thread. Not the same as a user nor a
 * guild member.
 *
 * @link https://discord.com/developers/docs/resources/channel#thread-member-object
 *
 * @since 7.0.0
 *
 * @property      string|null      $id             ID of the thread.
 * @property      string|null      $user_id        ID of the user that the member object represents.
 * @property-read User|null        $user           The user that the member object represents.
 * @property      Carbon           $join_timestamp The time that the member joined the thread.
 * @property      int              $flags          Flags relating to the member. Only used for client notifications.
 * @property-read GuildMember|null $member         Additional information about the user.
 */
class Member extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'user_id',
        'join_timestamp',
        'flags',
        'member',
        // @internal and events only
        'guild_id',
    ];

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
     *
     * @throws \Exception
     */
    protected function getJoinTimestampAttribute(): Carbon
    {
        return new Carbon($this->attributes['join_timestamp']);
    }

    /**
     * Returns the guild member that the thread member represents.
     *
     * @return GuildMember|null
     *
     * @since 10.0.0
     */
    protected function getMemberAttribute(): ?GuildMember
    {
        if (! isset($this->attributes['member'])) {
            if ($guild = $this->getGuildAttribute()) {
                return $guild->members->get('id', $this->user_id);
            }

            return null;
        }

        $memberData = (array) $this->attributes['member'];
        if (isset($this->guild_id)) {
            $memberData['guild_id'] = $this->guild_id;
        }

        return $this->factory->part(Member::class, $memberData, true);
    }

    /**
     * Returns the guild attribute based on internal `guild_id`.
     *
     * @return Guild|null The guild attribute.
     */
    private function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Attempts to remove the member from the thread.
     *
     * @link https://discord.com/developers/docs/resources/channel#remove-thread-member
     *
     * @return ExtendedPromiseInterface
     */
    public function remove(): ExtendedPromiseInterface
    {
        return $this->http->delete(Endpoint::bind(Endpoint::THREAD_MEMBER, $this->id, $this->user_id));
    }
}
