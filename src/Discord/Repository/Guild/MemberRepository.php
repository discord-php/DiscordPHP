<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains members of a guild.
 *
 * @see \Discord\Parts\User\Member
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Member|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Member|null first()                     Returns the first element of the collection.
 * @method Member|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Member|null find(callable $callback)    Runs a filter callback over the repository.
 */
class MemberRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_MEMBERS,
        'get' => Endpoint::GUILD_MEMBER,
        'update' => Endpoint::GUILD_MEMBER,
        'delete' => Endpoint::GUILD_MEMBER,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Member::class;

    /**
     * Alias for delete.
     *
     * @see https://discord.com/developers/docs/resources/guild#remove-guild-member
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
