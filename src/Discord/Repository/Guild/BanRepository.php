<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Guild;

use Discord\Helpers\Deferred;
use Discord\Parts\Guild\Ban;
use Discord\Parts\User\Member;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * Contains bans on users.
 *
 * @see \Discord\Parts\Guild\Ban
 * @see \Discord\Parts\Guild\Guild
 */
class BanRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $discrim = 'user_id';

    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => 'guilds/:guild_id/bans',
        'delete' => 'guilds/:guild_id/bans/:user_id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Ban::class;

    /**
     * Bans a member from the guild.
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

        if ($member instanceof Member) {
            $member = $member->id;
        }

        if (! is_null($daysToDeleteMessages)) {
            $content['delete-message-days'] = $daysToDeleteMessages;
        }

        if (! is_null($reason)) {
            $content['reason'] = $reason;
        }

        $this->http->put(
            $this->replaceWithVariables('guilds/:guild_id/bans/').$member,
            empty($content) ? null : $content
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
