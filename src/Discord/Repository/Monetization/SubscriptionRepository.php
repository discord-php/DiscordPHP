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

use Discord\Helpers\Collection;
use Discord\Http\Endpoint;
use Discord\Parts\Monetization\Subscription;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\AbstractRepository;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contains all Subscriptions for a given SKU.
 *
 * @see \Discord\Parts\Monetization\Subscription
 *
 * @since 10.15.0
 */
class SubscriptionRepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::SKU_SUBSCRIPTIONS,
        'get' => Endpoint::SKU_SUBSCRIPTION,
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = Subscription::class;

    /**
     * Returns all subscriptions for a given SKU, optionally filtered by user.
     *
     * @link https://discord.com/developers/docs/resources/subscription#list-sku-subscriptions
     *
     * @param array                       $options
     * @param Member|User|string|int|null $options['user_id'] User ID to filter subscriptions by. Required except for OAuth queries.
     * @param string|int|null             $options['before']  List subscriptions before this ID.
     * @param string|int|null             $options['after']   List subscriptions after this ID.
     * @param int|null                    $options['limit']   Number of results to return (1-100). Default 50.
     *
     * @throws \RangeException
     *
     * @return \React\Promise\PromiseInterface<\Discord\Helpers\Collection<Subscription>>
     */
    public function getSubscriptions(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined(['user_id', 'before', 'after', 'limit']);
        $resolver->setAllowedTypes('user_id', ['string', 'int', Member::class, User::class]);
        $resolver->setAllowedTypes('before', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('after', ['string', 'int', 'null']);
        $resolver->setAllowedTypes('limit', ['int', 'null']);
        $resolver->setAllowedValues('limit', function ($value) {
            return $value === null || ($value >= 1 && $value <= 100);
        });

        $options = $resolver->resolve($options);

        $endpoint = Endpoint::bind(
            Endpoint::SKU_SUBSCRIPTIONS,
            $this->vars['sku_id']
        );

        if (isset($options['user_id'])) {
            $endpoint->addQuery('user_id', $options['user_id'] instanceof Member || $options['user_id'] instanceof User ? $options['user_id']->id : $options['user_id']);
        }
        if (isset($options['before'])) {
            $endpoint->addQuery('before', $options['before']);
        }
        if (isset($options['after'])) {
            $endpoint->addQuery('after', $options['after']);
        }
        if (isset($options['limit'])) {
            $endpoint->addQuery('limit', $options['limit']);
        }

        return $this->http->get($endpoint)->then(function ($responses) {
            $subscriptions = Collection::for(Subscription::class);

            foreach ($responses as $response) {
                $subscriptions->pushItem($this->get('id', $response->id) ?: $this->create($response, true));
            }

            return $subscriptions;
        });
    }
}
