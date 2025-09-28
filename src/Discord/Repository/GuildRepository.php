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

use function React\Promise\resolve;

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
     * @inheritDoc
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
     * @inheritDoc
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
        if (! is_string($guild)) {
            $guild = $guild->id;
        }

        return $this->http
            ->delete(Endpoint::bind(Endpoint::USER_CURRENT_GUILD, $guild))
            ->then(fn () => $this->cache->delete($guild)->then(fn ($success) => $this));
    }

    /**
     * Returns the guild preview object for the given id. If the bot is not in the guild, then the guild must be discoverable.
     * Rejects with 10004 Unknown Guild if the guild does not exist or the bot is not in the guild and it is not discoverable.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-preview
     *
     * @param Guild|string $guild_id
     *
     * @return PromiseInterface<?GuildPreview>
     *
     * @since 10.19.0
     */
    public function preview($guild_id): PromiseInterface
    {
        if (! is_string($guild_id)) {
            $guild_id = $guild_id->id;
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_PREVIEW, $guild_id))
            ->then(fn ($data) => $data ? $this->factory->create(GuildPreview::class, $data, true) : null);
    }

    /**
     * Returns a list of partial guild objects the current user is a member of.
     * For OAuth2, requires the guilds scope.
     *
     * This endpoint returns 200 guilds by default, which is the maximum number of guilds a non-bot user can join.
     * Therefore, pagination is not needed for integrations that need to get a list of the users' guilds.
     *
     * @link https://discord.com/developers/docs/resources/user#get-current-user-guilds
     *
     * @param ?string|null $before      Get guilds before this guild ID.
     * @param ?string|null $after       Get guilds after this guild ID.
     * @param ?int|null    $limit       Max number of guilds to return (1-200). Defaults to 200.
     * @param ?bool|null   $with_counts Include approximate member and presence counts in response. Defaults to false.
     *
     * @throws \InvalidArgumentException No valid parameters to query.
     *
     * @return PromiseInterface<self>
     *
     * @since 10.32.0
     */
    public function getCurrentUserGuilds(array $params): PromiseInterface
    {
        $allowed = ['before', 'after', 'limit', 'with_counts'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to query.');
        }

        return $this->http->get(Endpoint::USER_CURRENT_GUILDS, $params)->then(function ($response) {
            $promise = resolve(true);

            foreach ($response as $data) {
                $promise = $promise
                    ->then(fn ($success) => $this->cache->get($data->id))
                    ->then(function ($part) use ($data) {
                        if ($part !== null) {
                            return $this->cache->set($data->id, $part->fill($data));
                        }

                        return $this->cache->set($data->id, $this->factory->create(Guild::class, $data, true));
                    });
            }

            return $promise;
        })->then(fn ($success) => $this);
    }
}
