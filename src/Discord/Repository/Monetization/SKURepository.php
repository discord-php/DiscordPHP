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
use Discord\Http\Endpoint;
use Discord\Parts\Monetization\SKU;
use Discord\Repository\AbstractRepository;

/**
 * Contains all SKUs for a given application.
 *
 * Because of how our SKU and subscription systems work, you will see two SKUs for your subscription offering.
 *
 * For integration and testing entitlements for Subscriptions, you should use the SKU with type: 5.
 *
 * @see \Discord\Parts\Monetization\SKU
 * @see \Discord\Parts\User\Client
 *
 * @since 10.15.0
 */
class SKURepository extends AbstractRepository
{
    /**
     * {@inheritDoc}
     */
    protected $endpoints = [
        'all' => Endpoint::APPLICATION_SKUS
    ];

    /**
     * {@inheritDoc}
     */
    protected $class = SKU::class;

    /**
     * {@inheritDoc}
     */
    public function __construct(Discord $discord, array $vars = [])
    {
        $vars['application_id'] = $discord->application->id;

        parent::__construct($discord, $vars);
    }
}
