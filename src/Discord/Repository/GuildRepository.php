<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Parts\Guild\Guild;
use Discord\Helpers\Deferred;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains guilds that the user is in.
 *
 * @see \Discord\Parts\Guild\Guild
 */
class GuildRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'users/@me/guilds',
        'get' => 'guilds/:id',
        'create' => 'guilds',
        'update' => 'guilds/:id',
        'delete' => 'guilds/:id',
        'leave' => 'users/@me/guilds/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Guild::class;

    /**
     * Causes the client to leave a guild.
     *
     * @param Guild|snowflake $guild
     *
     * @return ExtendedPromiseInterface
     */
    public function leave($guild): ExtendedPromiseInterface
    {
        $deferred = new Deferred();

        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        $this->http->delete("users/@me/guilds/{$guild}")->done(function () use ($guild, $deferred) {
            $this->pull('id', $guild);
            $deferred->resolve();
        }, \React\Partial\bind([$deferred, 'reject']));

        return $deferred->promise();
    }
}
