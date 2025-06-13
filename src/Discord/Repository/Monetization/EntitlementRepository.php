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

namespace Discord\Repository\Monetization;

use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Monetization\Entitlement;
use Discord\Parts\OAuth\Application;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use React\Promise\PromiseInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contains all entitlements for a given app, active and expired.
 *
 * @see \Discord\Parts\Monetization\Entitlement
 * @see \Discord\Parts\User\Client
 *
 * @since 10.15.0
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
        'delete' => Endpoint::APPLICATION_ENTITLEMENT
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
     * For One-Time Purchase consumable SKUs, marks a given entitlement for the user as consumed. The entitlement will have consumed: true when using List Entitlements.
     *
     * @link https://discord.com/developers/docs/resources/entitlement#consume-an-entitlement
     *
     * @param Entitlement|string $entitlement
     *
     * @return PromiseInterface
     */
    public function consume($entitlement): PromiseInterface
    {
        if ($entitlement instanceof Entitlement) {
            $entitlement = $entitlement->id;
        }

        return $this->http->post(Endpoint::APPLICATION_ENTITLEMENT_CONSUME, [
            'application_id' => $this->vars['application_id'],
            'entitlement_id' => $entitlement,
        ]);
    }

    /**
     * Returns all entitlements for a given app, active and expired.
     *
     * @link https://discord.com/developers/docs/resources/channel#get-channel-messages
     *
     * @param array                         $options                    Array of options.
     * @param Application|string|int|null   $options['application_id']  Application ID to look up entitlements for. Defaults to the bot's application ID.
     * @param Member|User|string|int|null   $options['user_id']         User ID to look up entitlements for.
     * @param array|string|int|null         $options['sku_ids']         Optional list of SKU IDs to check entitlements for.
     * @param Entitlement|string|int|null   $options['before']          Retrieve entitlements before this entitlement ID.
     * @param Entitlement|string|int|null   $options['after']           Retrieve entitlements after this entitlement ID.
     * @param int|null                      $options['limit']           Number of entitlements to return, 1-100, default 100.
     * @param Guild|string|int|null         $options['guild_id']        Guild ID to look up entitlements for.
     * @param bool|null                     $options['exclude_ended']   Whether ended entitlements should be omitted. Defaults to false.
     * @param bool|null                     $options['exclude_deleted'] Whether deleted entitlements should be omitted. Defaults to true.
     *
     * @throws \RangeException
     *
     * @return PromiseInterface<Collection<Entitlement[]>>
     * @todo Make it in a trait along with Thread
     */
    public function getEntitlements(array $options = []): PromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['application_id' => $this->vars['application_id']]);
        $resolver->setAllowedTypes('application_id', [Application::class, 'string', 'int']);
        $resolver->setAllowedTypes('user_id', ['string', 'int', Member::class, User::class]);
        $resolver->setAllowedTypes('sku_ids', ['array', 'string, int']);
        $resolver->setAllowedTypes('before', [Entitlement::class, 'string', 'int']);
        $resolver->setAllowedTypes('after', [Entitlement::class, 'string', 'int']);
        $resolver->setAllowedTypes('limit', 'integer');
        $resolver->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100));
        $resolver->setAllowedTypes('guild_id', [Guild::class, 'string', 'integer']);
        $resolver->setAllowedTypes('exclude_ended', ['bool', null]);
        $resolver->setAllowedTypes('exclude_deleted', ['bool', null]);

        $options = $resolver->resolve($options);

        $endpoint = Endpoint::bind(
            Endpoint::APPLICATION_ENTITLEMENTS,
            $options['application_id'] instanceof Application ? $options['application_id']->id : $options['application_id']
        );

        if (isset($options['user_id'])) {
            $endpoint->addQuery('user_id', $options['user_id'] instanceof Member || $options['user_id'] instanceof User ? $options['user_id']->id : $options['user_id']);
        }
        if (isset($options['sku_ids'])) {
            $endpoint->addQuery('sku_ids', is_array($options['sku_ids']) ? implode(',', $options['sku_ids']) : $options['sku_ids']);
        }
        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before'] instanceof Entitlement ? $options['before']->id : $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after'] instanceof Entitlement ? $options['after']->id : $options['after']);
        }
        if (isset($options['limit'])) {
            $endpoint->addQuery('limit', $options['limit']);
        }
        if (isset($options['guild_id'])) {
            $endpoint->addQuery('guild_id', $options['guild_id'] instanceof Guild ? $options['guild_id']->id : $options['guild_id']);
        }
        if (isset($options['exclude_ended'])) {
            $endpoint->addQuery('exclude_ended', $options['exclude_ended']);
        }
        if (isset($options['exclude_deleted'])) {
            $endpoint->addQuery('exclude_deleted', $options['exclude_deleted']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            $entitlements = Collection::for(Entitlement::class);

            foreach ($responses as $response) {
                $entitlements->pushItem($this->get('id', $response->id) ?: $this->create($response, true));
            }

            return $entitlements;
        });
    }

    /**
     * Creates a test entitlement to a given SKU for a given guild or user. Discord will act as though that user or guild has entitlement to your premium offering.
     *
     * This endpoint returns a partial entitlement object. It will not contain subscription_id, starts_at, or ends_at, as it's valid in perpetuity.
     *
     * After creating a test entitlement, you'll need to reload your Discord client. After doing so, you'll see that your server or user now has premium access.
     *
     * @link https://discord.com/developers/docs/resources/entitlement#create-test-entitlement
     *
     * @param array  $data
     * @param string $data['sku_id']     ID of the SKU to grant the entitlement to.
     * @param string $data['owner_id']   ID of the guild or user to grant the entitlement to.
     * @param int    $data['owner_type'] 1 for a guild subscription, 2 for a user subscription.
     *
     * @return PromiseInterface<Entitlement>
     */
    public function createTestEntitlement(array $data): PromiseInterface
    {
        if (!isset($data['sku_id'], $data['owner_id'], $data['owner_type'])) {
            throw new \DomainException('sku_id, owner_id, and owner_type are required to create a test entitlement.');
        }

        $payload = [
            'sku_id'     => $data['sku_id'],
            'owner_id'   => $data['owner_id'],
            'owner_type' => $data['owner_type'],
        ];

        return $this->http
            ->post(Endpoint::bind(Endpoint::APPLICATION_ENTITLEMENTS, $this->vars['application_id']), $payload)
            ->then(function ($response) {
                $part = $this->create((array) $response, true);
                return $this->cache->set($part->{$this->discrim}, $part)->then(fn ($success) => $part);
            });
    }

    /**
     * Deletes a currently-active test entitlement. Discord will act as though that user or guild no longer has entitlement to your premium offering.
     *
     * @link https://discord.com/developers/docs/resources/entitlement#delete-test-entitlement
     *
     * @param Entitlement|string $entitlement The entitlement or entitlement ID to delete.
     *
     * @return PromiseInterface
     */
    public function deleteTestEntitlement($entitlement): PromiseInterface
    {
        if ($entitlement instanceof Entitlement) {
            $entitlement = $entitlement->id;
        }

        $endpoint = Endpoint::bind(
            Endpoint::APPLICATION_ENTITLEMENT,
            $this->vars['application_id'],
            $entitlement
        );

        return $this->http->delete($endpoint);
    }
}
