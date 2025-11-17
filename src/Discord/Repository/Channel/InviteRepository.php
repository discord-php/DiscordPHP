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

namespace Discord\Repository\Channel;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Invite;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

/**
 * Contains invites of a channel.
 *
 * @see Invite
 * @see \Discord\Parts\Channel\Channel
 *
 * @since 4.0.0
 *
 * @method Invite|null get(string $discrim, $key)
 * @method Invite|null pull(string|int $key, $default = null)
 * @method Invite|null first()
 * @method Invite|null last()
 * @method Invite|null find(callable $callback)
 */
class InviteRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::CHANNEL_INVITES,
        'get' => Endpoint::INVITE,
        'create' => Endpoint::CHANNEL_INVITES,
        'delete' => Endpoint::INVITE,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Invite::class;

    /**
     * Freshens the repository cache.
     *
     * @param array        $queryparams              Query string params to add to the request (no validation)
     * @param ?bool|null   $with_counts              Whether the invite should contain approximate member counts.
     * @param ?string|null $guild_scheduled_event_id The guild scheduled event to include with the invite.
     * @param ?bool|null   $with_permissions         Whether the invite should contain the `is_nickname_changeable` field.
     *
     * @return PromiseInterface<static>
     *
     * @throws \Exception
     */
    public function freshen(array $queryparams = []): PromiseInterface
    {
        return parent::freshen($queryparams);
    }
}
