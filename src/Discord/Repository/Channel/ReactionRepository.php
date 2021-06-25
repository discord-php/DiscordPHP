<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Parts\Channel\Reaction;
use Discord\Repository\AbstractRepository;

/**
 * Contains invites on a message.
 *
 * @see \Discord\Parts\Channel\Message
 * @see Reaction
 */
class ReactionRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [];

    /**
     * @inheritdoc
     */
    protected $class = Reaction::class;
}
