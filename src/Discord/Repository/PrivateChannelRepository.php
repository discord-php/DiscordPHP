<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;

/**
 * Contains private channels and groups that the user has access to.
 *
 * @see Discord\Parts\Channel\Channel
 */
class PrivateChannelRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [];

    /**
     * {@inheritdoc}
     */
    protected $part = Channel::class;
}
