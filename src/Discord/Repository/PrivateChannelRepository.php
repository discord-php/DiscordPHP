<?php

namespace Discord\Repository;

use Discord\Parts\Channel\Channel;
use Discord\Repository\AbstractRepository;

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