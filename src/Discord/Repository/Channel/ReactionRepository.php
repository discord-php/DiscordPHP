<?php

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
     * {@inheritdoc}
     */
    protected $endpoints = [];

    /**
     * {@inheritdoc}
     */
    protected $class = Reaction::class;
}
