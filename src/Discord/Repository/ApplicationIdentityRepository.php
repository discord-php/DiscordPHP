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

namespace Discord\Repository;

use Discord\Helpers\ExCollectionInterface;
use Discord\Http\Endpoint;
use Discord\Parts\OAuth\ApplicationIdentity;
use Discord\Parts\OAuth\ApplicationIdentityProfile;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * The links between Discord users and their accounts in external systems, recorded for an application,
 * and the game stats profiles published for them.
 *
 * Identities are keyed by the external account rather than by an id, so there is no generic fetch:
 * look them up by user or by external id instead.
 *
 * @see ApplicationIdentity
 * @see \Discord\Parts\OAuth\Application
 *
 * @since 10.59.0
 *
 * @link https://docs.discord.com/developers/resources/application-identity-profile
 *
 * @method ApplicationIdentity|null get(string $discrim, $key)
 * @method ApplicationIdentity|null first()
 * @method ApplicationIdentity|null last()
 * @method ApplicationIdentity|null find(callable $callback)
 */
class ApplicationIdentityRepository extends AbstractRepository
{
    /**
     * @inheritDoc
     */
    protected $discrim = 'provider_issued_user_id';

    /**
     * @inheritDoc
     */
    protected $endpoints = [];

    /**
     * @inheritDoc
     */
    protected $class = ApplicationIdentity::class;

    /**
     * Returns the external identities a user has linked to the application.
     *
     * The user must have authorized the application with the `application_identities.write` scope.
     *
     * @link https://docs.discord.com/developers/resources/application-identity-profile#get-application-identities-by-user-id
     *
     * @param User|string $user The user or user id.
     *
     * @return PromiseInterface<ExCollectionInterface<ApplicationIdentity>|ApplicationIdentity[]>
     */
    public function forUser($user): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::USER_APPLICATION_IDENTITIES, $this->userId($user), $this->vars['application_id']))
            ->then($this->collectIdentities(...));
    }

    /**
     * Returns the identities recorded for the Discord user currently linked to an external account.
     *
     * @link https://docs.discord.com/developers/resources/application-identity-profile#get-application-identities-by-external-id
     *
     * @param string      $provider_type           The external account provider type.
     * @param string      $provider_issued_user_id The user's ID in the external system.
     * @param string|null $provider_id             Provider-specific identifier, when one is needed to tell identities apart.
     *
     * @return PromiseInterface<ExCollectionInterface<ApplicationIdentity>|ApplicationIdentity[]> Empty when no identity matches.
     */
    public function findByExternalId(string $provider_type, string $provider_issued_user_id, ?string $provider_id = null): PromiseInterface
    {
        $endpoint = Endpoint::bind(Endpoint::APPLICATION_IDENTITIES_BY_EXTERNAL_ID, $this->vars['application_id'], $provider_type, $provider_issued_user_id);

        if (null !== $provider_id) {
            $endpoint->addQuery('provider_id', $provider_id);
        }

        return $this->http->get($endpoint)->then($this->collectIdentities(...));
    }

    /**
     * Deletes an identity.
     *
     * @link https://docs.discord.com/developers/resources/application-identity-profile#delete-application-identity
     *
     * @param ApplicationIdentity $part   The identity to delete.
     * @param string|null         $reason Unused; identities have no audit log.
     *
     * @return PromiseInterface<ApplicationIdentity>
     */
    public function delete($part, ?string $reason = null): PromiseInterface
    {
        if (! $part instanceof ApplicationIdentity) {
            return reject(new \InvalidArgumentException('Deleting an application identity takes the identity itself, which carries the user and external account it links.'));
        }

        $payload = null === $part->provider_id ? [] : ['provider_id' => $part->provider_id];

        return $this->http->post(Endpoint::bind(Endpoint::USER_APPLICATION_IDENTITY_DELETE, $part->user_id, $this->vars['application_id'], $part->provider_type, $part->provider_issued_user_id), $payload)
            ->then(static fn () => $part);
    }

    /**
     * Returns the game stats profile the application published for one of a user's identities.
     *
     * The user must have authorized the application with the `application_identities.write` scope.
     *
     * @link https://docs.discord.com/developers/resources/application-identity-profile#get-application-identity-profile
     *
     * @param User|string $user                    The user or user id.
     * @param string      $provider_issued_user_id The user's ID in the external system.
     *
     * @return PromiseInterface<ApplicationIdentityProfile>
     */
    public function getProfile($user, string $provider_issued_user_id): PromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::APPLICATION_USER_IDENTITY_PROFILE, $this->vars['application_id'], $this->userId($user), $provider_issued_user_id))
            ->then(fn ($response) => $this->factory->part(ApplicationIdentityProfile::class, (array) $response, true));
    }

    /**
     * Publishes the game stats profile for one of a user's identities.
     *
     * The `data` field is replaced in full on every update: anything left out of it is removed.
     *
     * @link https://docs.discord.com/developers/resources/application-identity-profile#update-application-identity-profile
     *
     * @param User|string $user                    The user or user id.
     * @param string      $provider_issued_user_id The user's ID in the external system.
     * @param array       $data
     * @param ?string     $data['username']        The user's username in the external system.
     * @param ?array      $data['data']            The profile data: `primary` pre-configured stats and `dynamic` custom fields.
     *
     * @return PromiseInterface
     */
    public function updateProfile($user, string $provider_issued_user_id, array $data): PromiseInterface
    {
        return $this->http->patch(Endpoint::bind(Endpoint::APPLICATION_USER_IDENTITY_PROFILE, $this->vars['application_id'], $this->userId($user), $provider_issued_user_id), $data);
    }

    /**
     * Turns a wrapped list of identities into a collection.
     *
     * Unkeyed: two providers can issue the same user id, and a keyed collection would drop one of them.
     *
     * @param object|array $response The response, with its identities under `identities`.
     *
     * @return ExCollectionInterface<ApplicationIdentity>|ApplicationIdentity[]
     */
    protected function collectIdentities($response): ExCollectionInterface
    {
        /** @var ExCollectionInterface<ApplicationIdentity> $collection */
        $collection = $this->discord->getCollectionClass()::for(ApplicationIdentity::class, null);

        foreach (((array) $response)['identities'] ?? [] as $identity) {
            $collection->pushItem($this->factory->part(ApplicationIdentity::class, (array) $identity, true));
        }

        return $collection;
    }

    /**
     * The id of a user given as a part or already as an id.
     *
     * @param Part|string $user
     *
     * @return string
     */
    protected function userId($user): string
    {
        return $user instanceof Part ? $user->id : $user;
    }
}
