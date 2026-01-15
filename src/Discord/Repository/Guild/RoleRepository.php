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
use Discord\Parts\Guild\Role;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains roles of a guild.
 *
 * @since 4.0.0
 *
 * @see Role
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Role|null get(string $discrim, $key)
 * @method Role|null pull(string|int $key, $default = null)
 * @method Role|null first()
 * @method Role|null last()
 * @method Role|null find(callable $callback)
 */
class RoleRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_ROLES,
        'get' => Endpoint::GUILD_ROLE,
        'create' => Endpoint::GUILD_ROLES,
        'update' => Endpoint::GUILD_ROLE,
        'delete' => Endpoint::GUILD_ROLE,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Role::class;

    /**
     * Get member counts for every role in the guild.
     *
     * @return PromiseInterface<array<string, int>> [role_id => member_count]
     */
    public function getMemberCounts(): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_ROLES_MEMBER_COUNTS, $this->vars['guild_id']))->then(fn ($response) => (array) $response);
    }

    /**
     * Gets the highest role in the guild for the bot.
     *
     * @return Role|null The highest role or null if guild is not available.
     *
     * @since 10.40.0
     */
    public function getCurrentMemberHighestRole(): ?Role
    {
        if (! $guild = $this->discord->guilds->get('id', $this->vars['guild_id'])) {
            return null;
        }

        if (! $botMember = $guild->members->get('id', $this->discord->id)) {
            return null;
        }

        /** @var array<string, Role> */
        $role = $guild->roles
            ->filter(fn (Role $role) => $botMember->roles->has($role->id))
            ->sort(fn (Role $a, Role $b) => $b->comparePosition($a))
            ->shift() ?? [];

        /** @var Role|null */
        return array_shift($role);
    }
}
