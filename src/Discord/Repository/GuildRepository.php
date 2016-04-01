<?php

namespace Discord\Repository;

use Discord\Parts\Guild\Guild;
use Discord\Repository\AbstractRepository;

class GuildRepository extends AbstractRepository
{
	/**
     * {@inheritdoc}
     */
    protected $endpoints = [
    	'all'    => 'users/@me/guilds',
        'get'    => 'guilds/:id',
        'create' => 'guilds',
        'update' => 'guilds/:id',
        'delete' => 'guilds/:id',
        'leave'  => 'users/@me/guilds/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $part = Guild::class;
}
