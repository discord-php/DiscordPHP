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
 * Contains reactions on a message.
 *
 * @see \Discord\Parts\Channel\Message
 * @see Reaction
 *
 * @method Reaction|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Reaction|null first()                     Returns the first element of the collection.
 * @method Reaction|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Reaction|null find(callable $callback)    Runs a filter callback over the repository.
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
