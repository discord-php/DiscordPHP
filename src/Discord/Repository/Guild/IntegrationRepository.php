<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Integration;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains integrations on a guild.
 *
 * @see Integration
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 7.0.0
 *
 * @method Integration|null get(string $discrim, $key)
 * @method Integration|null pull(string|int $key, $default = null)
 * @method Integration|null first()
 * @method Integration|null last()
 * @method Integration|null find(callable $callback)
 */
class IntegrationRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_INTEGRATIONS,
        'delete' => Endpoint::GUILD_INTEGRATION,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Integration::class;

    /**
     * Syncs an integration for the guild.
     *
     * Requires the MANAGE_GUILD permission.
     *
     * Returns a 204 empty response on success.
     *
     * Fires Guild Integrations Update and Integration Update Gateway events.
     *
     * @link https://docs.discord.com/developers/resources/guild#sync-guild-integration
     *
     * @param string $integrationId
     *
     * @return PromiseInterface
     */
    public function sync(string $integration_id): PromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::GUILD_INTEGRATION_SYNC, $this->vars['guild_id'], $integration_id));
    }
}
