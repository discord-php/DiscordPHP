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
     * @param User|Member|string $user    The User to ban.
     * @param array|int          $options Array of Ban options 'delete_message_seconds' or 'delete_message_days' (deprecated).
     * @param string|null        $reason  Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface
     */
    public function ban($user, $options = null, ?string $reason = null): ExtendedPromiseInterface
    {
        $content = [];
        $headers = [];

        if ($user instanceof Member) {
            $user = $user->user;
        } elseif (! ($user instanceof User)) {
            $user = $this->factory->part(User::class, ['id' => $user], true);
        }

        // TODO: v8.x remove all 'delete_message_days' and strict $options to array
        if (is_int($options)) {
            $content['delete_message_days'] = $options;
        } elseif (is_array($options)) {
            $resolver = new OptionsResolver();
            $resolver->setDefined([
                'delete_message_seconds',
                'delete_message_days',
            ])
            ->setAllowedTypes('delete_message_seconds', 'int')
            ->setAllowedTypes('delete_message_days', 'int')
            ->setAllowedValues('delete_message_seconds', function ($value) {
                return $value >= 0 && $value <= 604800;
            })
            ->setAllowedValues('delete_message_days', function ($value) {
                return $value >= 1 && $value <= 7;
            });

            $content = $resolver->resolve($options);
        }

        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->put(
            Endpoint::bind(Endpoint::GUILD_BAN, $this->vars['guild_id'], $user->id),
            empty($content) ? null : $content,
            $headers
        )->then(function () use ($user, $reason) {
            $ban = $this->factory->create(Ban::class, [
                'user' => (object) $user->getRawAttributes(),
                'reason' => $reason,
                'guild_id' => $this->vars['guild_id'],
            ], true);
            $this->pushItem($ban);

            return $ban;
        });
    }

    /**
     * Unbans a member from the guild.
     *
     * @see https://discord.com/developers/docs/resources/guild#remove-guild-ban
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
            if ($banPart = $this->get('user_id', $ban)) {
                $ban = $banPart;
            }
        }

        return $this->delete($ban, $reason);
    }
}
