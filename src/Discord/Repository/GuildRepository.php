<?php

declare(strict_types=1);

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
use Discord\Parts\Guild\GuildPreview;
use React\Promise\PromiseInterface;

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
        'preview' => Endpoint::GUILD_PREVIEW,
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
     * @return PromiseInterface<self>
     */
    public function leave($guild): PromiseInterface
    {
        if ($guild instanceof Guild) {
            $guild = $guild->id;
        }

        return $this->http
            ->delete(Endpoint::bind(Endpoint::USER_CURRENT_GUILD, $guild))
            ->then(fn () => $this->cache->delete($guild)->then(fn ($success) => $this));
    }

    /**
     * Returns the guild preview object for the given id. If the bot is not in the guild, then the guild must be discoverable.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-preview
     *
     * @param Guild|string $guild_id
     *
     * @return PromiseInterface<?GuildPreview>
     */
    public function preview($guild_id): PromiseInterface
    {
        if ($guild_id instanceof Guild) {
            $guild_id = $guild_id->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_PREVIEW, $guild_id))
            ->then(fn ($data) => $data ? $this->createOf(GuildPreview::class, $data) : null);
    }
}
