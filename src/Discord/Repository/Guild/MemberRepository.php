<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;

class MemberRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'get'    => 'guild/:guild_id/members/:id',
        'update' => 'guild/:guild_id/members/:id',
        'delete' => 'guild/:guild_id/members/:id',
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
     * @return \React\Promise\Promise 
     * @see self::delete()
     */
    public function kick($member)
    {
        return $this->delete($member);
    }
}
