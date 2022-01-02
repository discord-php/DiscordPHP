<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Thread;

use Discord\Http\Endpoint;
use Discord\Parts\Thread\Member;
use Discord\Repository\AbstractRepository;

/**
 * Contains members of a thread.
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
    protected $discrim = 'user_id';

    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::THREAD_MEMBERS,
        'get' => Endpoint::THREAD_MEMBER,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Member::class;
}
