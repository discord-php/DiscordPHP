<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;

/**
 * Contains private channels and groups that the user has access to.
 *
 * @see \Discord\Parts\Channel\Channel
 */
class PrivateChannelRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [];

    /**
     * @inheritdoc
     */
    protected $class = Channel::class;
}
