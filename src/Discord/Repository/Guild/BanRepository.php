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
use Discord\Parts\Guild\Ban;
use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains bans on users.
 *
 * @see \Discord\Parts\Guild\Ban
 * @see \Discord\Parts\Guild\Guild
 *
 * @method Ban|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Ban|null first()                     Returns the first element of the collection.
 * @method Ban|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Ban|null find(callable $callback)    Runs a filter callback over the repository.
 */
class BanRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $discrim = 'user_id';

    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_BANS,
        'get' => Endpoint::GUILD_BAN,
        'delete' => Endpoint::GUILD_BAN,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Ban::class;

    /**
     * Bans a member from the guild.
     *
     * @see https://discord.com/developers/docs/resources/guild#create-guild-ban
     *
     * @param Member|string $member
     * @param int|null      $daysToDeleteMessages
     * @param string|null   $reason
     *
     * @return ExtendedPromiseInterface
     */
    public function ban($member, ?int $daysToDeleteMessages = null, ?string $reason = null): ExtendedPromiseInterface
    {
        $deferred = new Deferred();
        $content = [];
        $headers = [];

        if ($member instanceof Member) {
            $member = $member->id;
        }

        if (! is_null($daysToDeleteMessages)) {
            $content['delete_message_days'] = $daysToDeleteMessages;
        }

        if (! is_null($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        $this->http->put(
            Endpoint::bind(Endpoint::GUILD_BAN, $this->vars['guild_id'], $member),
            empty($content) ? null : $content,
            $headers
        )->done(function ($response) use ($deferred) {
            $ban = $this->factory->create(Ban::class, $response, true);
            $this->push($ban);
            $deferred->resolve($ban);
        }, [$deferred, 'reject']);

        return $deferred->promise();
    }

    /**
     * Unbans a member from the guild.
     *
     * @see https://discord.com/developers/docs/resources/guild#remove-guild-ban
     *
     * @param Member|Ban|string $member
     *
     * @return ExtendedPromiseInterface
     */
    public function unban($member): ExtendedPromiseInterface
    {
        if ($member instanceof Member) {
            $member = $member->id;
        } elseif ($member instanceof Ban) {
            $member = $member->user_id;
        }

        return $this->delete($member);
    }
}
