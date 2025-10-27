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

namespace Discord\Repository\Guild;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Ban;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;
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
     * @inheritDoc
     */
    protected $discrim = 'user_id';

    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'all' => Endpoint::GUILD_BANS,
        'get' => Endpoint::GUILD_BAN,
        'delete' => Endpoint::GUILD_BAN,
    ];

    /**
     * @inheritDoc
     */
    protected $class = Ban::class;

    /**
     * Ban up to 200 users from a guild, and optionally delete previous messages sent by the banned users.
     * Requires both the BAN_MEMBERS and MANAGE_GUILD permissions.
     * Returns a 200 response on success, including the fields banned_users with the IDs of the banned users and failed_users with IDs that could not be banned or were already banned.
     *
     * @link https://discord.com/developers/docs/resources/guild#bulk-guild-ban
     *
     * @param User[]|string[] $users                             An array of user IDs to ban (up to 200).
     * @param array           $options                           Array of Ban options 'delete_message_seconds'.
     * @param ?int            $options['delete_message_seconds'] Number of seconds to delete messages for (0-604800).
     *
     * @throws \OverflowException If more than 200 user IDs are provided.
     *
     * @return PromiseInterface<array{banned_users: array<string, Ban>, failed_users: array<string>}>
     *
     * @since 10.40.0
     */
    public function banBulk($users, array $options = [], ?string $reason = null): PromiseInterface
    {
        $content = [];

        foreach ($users as &$user) {
            if (! is_string($user)) {
                $user = $user->id;
            }
            $content['user_ids'][] = $user;
        }

        if (count($content['user_ids']) > 200) {
            throw new \OverflowException('You can only ban up to 200 users at a time.');
        }

        $resolver = new OptionsResolver();
        $resolver
            ->setDefined(['delete_message_seconds'])
            ->setAllowedTypes('delete_message_seconds', 'int')
            ->setAllowedValues('delete_message_seconds', fn ($value) => $value >= 0 && $value <= 604800);

        $content = array_merge($content, $resolver->resolve($options));

        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_BAN_BULK, $this->vars['guild_id']), $content, $headers)
        ->then(function ($response) use ($reason) {
            $response = (array) $response;
            
            $banned_users = [];
            foreach ($response['banned_users'] ?? [] as $user_id) {
                /** @var Ban */
                $banned_users[$user_id] = $ban = $this->factory->part($this->class, [
                    'user_id' => $user_id,
                    'reason' => $reason,
                    'guild_id' => $this->vars['guild_id'],
                ], true);

                $this->cache->set($user_id, $ban);
            }

            return [
                'banned_users' => $banned_users,
                'failed_users' => $response['failed_users'] ?? [],
            ];
        });
    }

    /**
     * Bans a member from the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-ban
     *
     * @param User|Member|string $user    The User to ban.
     * @param array              $options Array of Ban options 'delete_message_seconds' or 'delete_message_days' (deprecated).
     * @param string|null        $reason  Reason for Audit Log.
     *
     * @return PromiseInterface<Ban>
     */
    public function ban($user, array $options = [], ?string $reason = null): PromiseInterface
    {
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
        ->setAllowedValues('delete_message_seconds', fn ($value) => $value >= 0 && $value <= 604800)
        ->setAllowedValues('delete_message_days', fn ($value) => $value === null || ($value >= 0 && $value <= 7));

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

            return $this->cache->set($ban->user_id, $ban)->then(fn () => $ban);
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
     * @return PromiseInterface
     */
    public function unban($ban, ?string $reason = null): PromiseInterface
    {
        if ($ban instanceof User || $ban instanceof Member) {
            $ban = $ban->id;
        }

        if (is_scalar($ban)) {
            return $this->cache->get($ban)->then(fn ($ban) => $this->delete($ban, $reason));
        }

        return $this->delete($ban, $reason);
    }
}
