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
use Discord\Parts\Guild\Guild;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Discord\Http\Exceptions\NoPermissionsException;

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
     * Freshens the repository cache with validated query params.
     *
     * Query options:
     * - `status` (string): One of `SUBMITTED`, `APPROVED`, or `REJECTED`.
     * - `limit` (int): Maximum number of join requests to return (1-100). Default 100.
     * - `before` (string|null): Snowflake to get join requests before this value.
     * - `after` (string|null): Snowflake to get join requests after this value.
     *
     * @param array $queryparams Query string params to add to the request.
     *
     * @return PromiseInterface<static>
     *
     * @throws \Exception
     */
    public function freshen(array $queryparams = []): PromiseInterface
    {
        $resolver = new OptionsResolver()
            ->setDefined(['status', 'limit', 'before', 'after'])
            ->setAllowedTypes('status', 'string')
            ->setAllowedTypes('limit', 'int')
            ->setAllowedTypes('before', ['string', 'null'])
            ->setAllowedTypes('after', ['string', 'null'])
            ->setDefaults(['limit' => 100])
            ->setAllowedValues('status', fn ($value) => in_array($value, ['SUBMITTED', 'APPROVED', 'REJECTED']))
            ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));

        $options = $resolver->resolve($queryparams);

        if (isset($options['before'], $options['after'])) {
            return reject(new \RangeException('Can only specify one of before after.'));
        }

        /** @var Guild $guild */
        $guild = $this->discord->guilds->get('id', $this->vars['guild_id']) ?? $this->factory->part(Guild::class, ['id' => $this->vars['guild_id']], true);
        if ($botperms = $guild->getBotPermissions()) {
            if (! ($botperms->kick_members || $botperms->manage_guild)) {
                return reject(new NoPermissionsException("You do not have permission to list join requests in the guild {$this->vars['guild_id']}."));
            }
        }

        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        foreach ($options as $k => $v) {
            if ($v === null) {
                continue;
            }
            $endpoint->addQuery($k, $v);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            return $this->cacheFreshen($response);
        });
    }

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
