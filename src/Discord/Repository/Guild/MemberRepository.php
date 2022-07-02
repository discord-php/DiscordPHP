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
use React\Promise\PromiseInterface;

/**
 * Contains members of a guild.
 *
 * @see \Discord\Parts\User\Member
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Member|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Member|null first()                     Returns the first element of the collection.
 * @method Member|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Member|null find(callable $callback)    Runs a filter callback over the repository.
 */
class MemberRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_MEMBERS,
        'get' => Endpoint::GUILD_MEMBER,
        'update' => Endpoint::GUILD_MEMBER,
        'delete' => Endpoint::GUILD_MEMBER,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Member::class;

    /**
     * Alias for `$member->delete()`.
     *
     * @see https://discord.com/developers/docs/resources/guild#remove-guild-member
     *
     * @param Member      $member The member to kick.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return PromiseInterface
     */
    public function kick(Member $member, ?string $reason = null): PromiseInterface
    {
        return $this->delete($member, $reason);
    }

    /**
     * @inheritdoc
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
                    $this->clear();
                }

                foreach ($response as $value) {
                    $value = array_merge($this->vars, (array) $value);
                    $part = $this->factory->create($this->class, $value, true);

                    $this->pushItem($part);
                }

                $paginate($part->id);
            }, [$deferred, 'reject']);
        })();

        return $deferred->promise();
    }
}
