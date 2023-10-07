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

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains guilds that the client is in.
 *
 * @see Guild
 *
 * @since 4.0.0
 *
 * @method Guild|null get(string $discrim, $key)
 * @method Guild|null pull(string|int $key, $default = null)
 * @method Guild|null first()
 * @method Guild|null last()
 * @method Guild|null find(callable $callback)
 */
class GuildRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::USER_CURRENT_GUILDS,
        'get' => Endpoint::GUILD,
        'create' => Endpoint::GUILDS,
        'update' => Endpoint::GUILD,
        'delete' => Endpoint::GUILD,
        'leave' => Endpoint::USER_CURRENT_GUILD,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Guild::class;

    /**
     * Causes the client to leave a guild.
     *
     * @link https://discord.com/developers/docs/resources/user#leave-guild
     *
     * @param Guild|string $guild
     *
     * @return ExtendedPromiseInterface
     */
    public function leave($guild): ExtendedPromiseInterface
    {
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::USER_CURRENT_GUILD, $guild))->then(function () use ($guild) {
            return $this->cache->delete($guild)->then(function ($success) {
                return $this;
            });
        });
    }
}
