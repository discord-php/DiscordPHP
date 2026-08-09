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
use Discord\Parts\Guild\GuildJoinRequests;
use Discord\Parts\Guild\JoinRequest;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;
use function React\Promise\reject;

/**
 * Contains join requests for a guild.
 *
 * @since 10.55.0
 *
 * @see JoinRequest
 * @see \Discord\Parts\Guild\Guild
 *
 * @method JoinRequest|null get(string $discrim, $key)
 * @method JoinRequest|null pull(string|int $key, $default = null)
 * @method JoinRequest|null first()
 * @method JoinRequest|null last()
 * @method JoinRequest|null find(callable $callback)
 */
class JoinRequestRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_JOIN_REQUESTS,
        'update' => Endpoint::GUILD_JOIN_REQUEST,
    ];

    /**
     * @inheritDoc
     */
    protected $class = JoinRequest::class;

    /**
     * @inheritDoc
     */
    protected function cacheFreshen($response): PromiseInterface
    {
        /** @var GuildJoinRequests $response */
        $response = $this->factory->part(GuildJoinRequests::class, (array) $response, true);
        $items = [];

        foreach ($response->guild_join_requests as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->part($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        return $this->cache->setMultiple($items)->then(fn ($success) => $this);
    }

    /**
     * Approve or reject a join request.
     *
     * @param JoinRequest|string $joinRequestId   The join request id or part.
     * @param string             $action          Either 'APPROVED' or 'REJECTED'.
     * @param string|null        $rejectionReason Optional rejection reason (max 160 chars).
     *
     * @return PromiseInterface<JoinRequest>
     */
    public function action($joinRequestId, string $action, ?string $rejectionReason = null): PromiseInterface
    {
        if ($joinRequestId instanceof JoinRequest) {
            $part = $joinRequestId;
        } else {
            $part = $this->factory->part($this->class, [$this->discrim => (string) $joinRequestId] + $this->vars, true);
        }

        $endpoint = new Endpoint($this->endpoints['update']);
        $endpoint->bindAssoc(array_merge($part->getRepositoryAttributes(), $this->vars));

        $payload = ['action' => $action];
        if ($rejectionReason !== null) {
            $payload['rejection_reason'] = $rejectionReason;
        }

        return $this->http->patch($endpoint, $payload)->then(function ($response) {
            $newPart = $this->factory->part($this->class, (array) $response, true);

            return $this->cache->set($newPart->{$this->discrim}, $newPart)->then(fn ($success) => $newPart);
        });
    }
}
