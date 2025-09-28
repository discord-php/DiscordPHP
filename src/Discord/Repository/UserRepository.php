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

namespace Discord\Repository;

use Discord\Http\Endpoint;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

/**
 * Contains users that the client shares guilds with.
 *
 * @see User
 *
 * @since 4.0.0
 *
 * @method User|null get(string $discrim, $key)
 * @method User|null pull(string|int $key, $default = null)
 * @method User|null first()
 * @method User|null last()
 * @method User|null find(callable $callback)
 */
class UserRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $endpoints = [
        'get' => Endpoint::USER,
    ];

    /**
     * @inheritDoc
     */
    protected $class = User::class;

    /**
     * Returns the user object of the requester's account.
     *
     * @param bool $fresh Whether to fetch a fresh copy from the API.
     *
     * @return PromiseInterface<User>
     *
     * @since 10.32.0
     */
    public function getCurrentUser(bool $fresh = false): PromiseInterface
    {
        if ($fresh) {
            return $this->__getCurrentUser();
        }

        return $this->cache->get($this->discord->id)->then(function ($part) {
            if ($part !== null) {
                return $part;
            }

            return $this->__getCurrentUser();
        });
    }

    /**
     * Returns the user object of the requester's account.
     * For OAuth2, this requires the identify scope, which will return the object without an email, and optionally the email scope, which returns the object with an email if the user has one.
     *
     * @link https://discord.com/developers/docs/resources/user#get-current-user
     *
     * @return PromiseInterface<User>
     *
     * @since 10.32.0
     */
    protected function __getCurrentUser()
    {
        return $this->http->get(Endpoint::USER_CURRENT)->then(function ($response) {
            $part = $this->factory->part(User::class, (array) $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }

    /**
     * Modify the requester's user account settings.
     * Returns a user object on success.
     * Fires a User Update Gateway event.
     *
     * @link https://discord.com/developers/docs/resources/user#modify-current-user
     *
     * @param array   $params
     * @param string  $username User's username, if changed may cause the user's discriminator to be randomized.
     * @param ?string $avatar   If passed, modifies the user's avatar.
     * @param ?string $banner   If passed, modifies the user's banner.
     *
     * @throws \InvalidArgumentException No valid parameters to modify.
     *
     * @return PromiseInterface<User>
     *
     * @since 10.32.0
     */
    public function modifyCurrentUser(array $params): PromiseInterface
    {
        $allowed = ['username', 'avatar', 'banner'];
        $params = array_filter(
            $params,
            fn ($key) => in_array($key, $allowed, true),
            ARRAY_FILTER_USE_KEY
        );

        if (empty($params)) {
            throw new \InvalidArgumentException('No valid parameters to modify.');
        }

        return $this->http->patch(Endpoint::USER_CURRENT, $params)->then(function ($response) {
            $part = $this->factory->part(User::class, (array) $response, true);

            return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
        });
    }
}
