<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains members of a guild.
 *
 * @see \Discord\Parts\User\Member
 * @see \Discord\Parts\Guild\Guild
 */
class MemberRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'guilds/:guild_id/members',
        'get' => 'guilds/:guild_id/members/:id',
        'update' => 'guilds/:guild_id/members/:id',
        'delete' => 'guilds/:guild_id/members/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Member::class;

    /**
     * Alias for delete.
     *
     * @param Member $member The member to kick.
     *
     * @return PromiseInterface
     *
     * @see self::delete()
     */
    public function kick(Member $member): PromiseInterface
    {
        return $this->delete($member);
    }
}
