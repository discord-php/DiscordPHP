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
 * Contains guilds that the user is in.
 *
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Guild|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Guild|null first()                     Returns the first element of the collection.
 * @method Guild|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Guild|null find(callable $callback)    Runs a filter callback over the repository.
 */
class GuildRepository extends AbstractRepository
{
    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    protected $class = Guild::class;

    /**
     * Causes the client to leave a guild.
     *
     * @see https://discord.com/developers/docs/resources/user#leave-guild
     *
     * @param Guild|snowflake $guild
     *
     * @return ExtendedPromiseInterface
     */
    public function leave($guild): ExtendedPromiseInterface
    {
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http->delete(Endpoint::bind(Endpoint::USER_CURRENT_GUILD, $guild))->then(function () use ($guild) {
            $this->pull('id', $guild);

            return $this;
        });
    }
}
