<?php

namespace Discord\Repository\Guild;

use Discord\Parts\Channel\Thread;
use Discord\Repository\AbstractRepository;

/**
 * Contains threads that belong to a guild.
 */
class ThreadRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [];

    /**
     * {@inheritDoc}
     */
    protected $class = Thread::class;
}