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
 * @see Reaction
 * @see \Discord\Parts\Channel\Message
 *
 * @method Reaction|null get(string $discrim, $key)
 * @method Reaction|null pull(string|int $key, $default = null)
 * @method Reaction|null first()
 * @method Reaction|null last()
 * @method Reaction|null find()
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
