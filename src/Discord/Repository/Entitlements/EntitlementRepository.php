<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository;

use Discord\Discord;
use Discord\Http\Endpoint;
use Discord\Parts\Entitlement;
use React\Promise\ExtendedPromiseInterface;


use function React\Promise\resolve;

/**
 * Contains entitlements of an application.
 *
 * @see Entitlement
 * @see \Discord\Parts\User\Client
 *
 * @since 10.0.0
 *
 * @method Entitlement|null get(string $discrim, $key)
 * @method Entitlement|null pull(string|int $key, $default = null)
 * @method Entitlement|null first()
 * @method Entitlement|null last()
 * @method Entitlement|null find(callable $callback)
 */
class EntitlementRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::APPLICATION_ENTITLEMENTS,
        'get' => Endpoint::APPLICATION_ENTITLEMENT,
        'create' => Endpoint::APPLICATION_ENTITLEMENTS,
        'delete' => Endpoint::APPLICATION_ENTITLEMENT,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Entitlement::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id;

        parent::__construct($discord, $vars);
    }

    /**
     * @param object $response
     *
     * @return ExtendedPromiseInterface<static>
     */
    protected function cacheFreshen($response): ExtendedPromiseInterface
    {
        foreach ($response as $value) foreach ($value as $value) {
            $value = array_merge($this->vars, (array) $value);
            $part = $this->factory->create($this->class, $value, true);
            $items[$part->{$this->discrim}] = $part;
        }

        if (empty($items)) {
            return resolve($this);
        }

        return $this->cache->setMultiple($items)->then(fn ($success) => $this);
    }

    /**
     * Creates a test entitlement to a given SKU for a given guild or user.
     *
     * @param string $sku_id     ID of the SKU to grant the entitlement to.
     * @param string $owner_id   ID of the guild or user to grant the entitlement to.
     * @param int    $owner_type 1 for a guild subscription, 2 for a user subscription.
     *
     * @throws \InvalidArgumentException
     *
     * @return ExtendedPromiseInterface<Entitlement>
     */
    public function createTestEntitlement(string $sku_id, string $owner_id, int $owner_type): ExtendedPromiseInterface
    {
        $allowed_owner_types = [Entitlement::OWNER_TYPE_GUILD, Entitlement::OWNER_TYPE_USER];

        if (! in_array($owner_type, $allowed_owner_types)) {
            throw new \InvalidArgumentException("The given owner type `{$owner_type}` is not valid.");
        }

        $payload = [
            'sku_id' => $sku_id,
            'owner_id' => $owner_id,
            'owner_type' => $owner_type,
        ];

        return $this->http->post(new Endpoint(Endpoint::APPLICATION_ENTITLEMENT), $payload)
            ->then(fn ($response) => $this->factory->create($this->class, (array) $response, true));
    }

    /*
    * Deletes a currently-active test entitlement.
    * Discord will act as though that user or guild no longer has entitlement to your premium offering.
    *
    * @param Entitlement $part
    *
    * @return ExtendedPromiseInterface
    */
   public function deleteTestEntitlement(Entitlement $part): ExtendedPromiseInterface
   {
       return $this->http->delete(Endpoint::bind(Endpoint::APPLICATION_ENTITLEMENT, ['application_id' => $part->application_id, 'entitlement_id' => $part->id]))
        ->then(fn ($response) => $this->cache->delete($part->{$this->discrim})
        ->then(fn ($success) => $part));
   }
}
