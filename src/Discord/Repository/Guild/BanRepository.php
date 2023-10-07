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

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Ban;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\ExtendedPromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contains bans for users of a guild.
 *
 * @see Ban
 * @see \Discord\Parts\Guild\Guild
 *
 * @since 4.0.0
 *
 * @method Ban|null get(string $discrim, $key)
 * @method Ban|null pull(string|int $key, $default = null)
 * @method Ban|null first()
 * @method Ban|null last()
 * @method Ban|null find(callable $callback)
 */
class BanRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $discrim = 'user_id';

    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_BANS,
        'get' => Endpoint::GUILD_BAN,
        'delete' => Endpoint::GUILD_BAN,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Ban::class;

    /**
     * Bans a member from the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-ban
     *
     * @param User|Member|string $user    The User to ban.
     * @param array              $options Array of Ban options 'delete_message_seconds' or 'delete_message_days' (deprecated).
     * @param string|null        $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Ban>
     */
    public function ban($user, array $options = [], ?string $reason = null): ExtendedPromiseInterface
    {
        $content = [];
        $headers = [];

        if ($user instanceof Member) {
            $user = $user->user;
        } elseif (! ($user instanceof User)) {
            $user = $this->factory->part(User::class, ['id' => $user], true);
        }

        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'delete_message_seconds',
            'delete_message_days',
        ])
        ->setAllowedTypes('delete_message_seconds', 'int')
        ->setAllowedTypes('delete_message_days', ['int', 'null'])
        ->setAllowedValues('delete_message_seconds', function ($value) {
            return $value >= 0 && $value <= 604800;
        })
        ->setAllowedValues('delete_message_days', function ($value) {
            return $value === null || ($value >= 0 && $value <= 7);
        });

        $content = $resolver->resolve($options);

        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(
            Endpoint::bind(Endpoint::GUILD_BAN, $this->vars['guild_id'], $user->id),
            empty($content) ? null : $content,
            $headers
        )->then(function () use ($user, $reason) {
            /** @var Ban */
            $ban = $this->factory->part(Ban::class, [
                'user' => (object) $user->getRawAttributes(),
                'reason' => $reason,
                'guild_id' => $this->vars['guild_id'],
            ], true);

            return $this->cache->set($ban->user_id, $ban)->then(function () use ($ban) {
                return $ban;
            });
        });
    }

    /**
     * Unbans a member from the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#remove-guild-ban
     *
     * @param User|Ban|string $ban    User or Ban Part, or User ID
     * @param string|null     $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function unban($ban, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($ban instanceof User || $ban instanceof Member) {
            $ban = $ban->id;
        }

        if (is_scalar($ban)) {
            return $this->cache->get($ban)->then(function ($ban) use ($reason) {
                return $this->delete($ban, $reason);
            });
        }

        return $this->delete($ban, $reason);
    }
}
