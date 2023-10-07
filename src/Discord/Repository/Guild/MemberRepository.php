<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Helpers\Deferred;
use Discord\Http\Endpoint;
use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains members of a guild.
 *
 * @since 4.0.0
 *
 * @see Member
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Member|null get(string $discrim, $key)
 * @method Member|null pull(string|int $key, $default = null)
 * @method Member|null first()
 * @method Member|null last()
 * @method Member|null find(callable $callback)
 */
class MemberRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_MEMBERS,
        'get' => Endpoint::GUILD_MEMBER,
        'update' => Endpoint::GUILD_MEMBER,
        'delete' => Endpoint::GUILD_MEMBER,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Member::class;

    /**
     * Alias for `$member->delete()`.
     *
     * @link https://discord.com/developers/docs/resources/guild#remove-guild-member
     *
     * @param Member      $member The member to kick.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function kick(Member $member, ?string $reason = null): ExtendedPromiseInterface
    {
        return $this->delete($member, $reason);
    }

    /**
     * {@inheritDoc}
     *
     * @param array $queryparams Query string params to add to the request, leave null to paginate all members (Warning: Be careful to use this on very large guild)
     */
    public function freshen(array $queryparams = null): ExtendedPromiseInterface
    {
        if (isset($queryparams)) {
            return parent::freshen($queryparams);
        }

        $endpoint = new Endpoint($this->endpoints['all']);
        $endpoint->bindAssoc($this->vars);

        $deferred = new Deferred();

        ($paginate = function ($afterId = 0) use (&$paginate, $deferred, $endpoint) {
            $endpoint->addQuery('limit', 1000);
            $endpoint->addQuery('after', $afterId);

            $this->http->get($endpoint)->then(function ($response) use ($paginate, $deferred, $afterId) {
                if (empty($response)) {
                    $deferred->resolve($this);

                    return;
                } elseif (! $afterId) {
                    $this->items = [];
                }

                foreach ($response as $value) {
                    $lastValueId = $value->user->id;
                }

                $this->cacheFreshen($response)->then(function () use ($paginate, $lastValueId) {
                    $paginate($lastValueId);
                });
            }, [$deferred, 'reject']);
        })();

        return $deferred->promise();
    }
}
