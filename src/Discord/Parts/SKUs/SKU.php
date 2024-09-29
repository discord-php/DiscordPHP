<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\SKUs;

use Discord\Parts\Part;
use Discord\Repository\SKUs\SubscriptionRepository;

/**
 * An SKU object represents a premium offering in the application that a user or guild can purchase.
 *
 * @link https://discord.com/developers/docs/resources/sku
 *
 * @since 10.0.0
 *
 * @property string                      $id              ID of the SKU.
 * @property int                         $type            Type of SKU.
 * @property string                      $application_id  ID of the parent application.
 * @property string                      $name            Customer-facing name of the premium offering.
 * @property string                      $slug            System-generated URL slug based on the SKU's name.
 * @property int                         $flags           SKU flags combined as a bitfield.
 * @property-read SubscriptionRepository $subscriptions Repository for the subscriptions that belong to this SKU.
 */
class SKU extends Part
{
    public const TYPE_DURABLE = 2;
    public const TYPE_CONSUMABLE = 3;
    public const TYPE_SUBSCRIPTION = 5;
    public const TYPE_SUBSCRIPTION_GROUP = 6;

    public const FLAG_AVAILABLE = 1 << 2;
    public const FLAG_GUILD_SUBSCRIPTION = 1 << 7;
    public const FLAG_USER_SUBSCRIPTION = 1 << 8;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'application_id',
        'name',
        'slug',
        'flags',
    ];

    /**
     * {@inheritDoc}
     */
    protected $repositories = [
        'subscriptions' => SubscriptionRepository::class,
    ];

    /**
     * Checks if the SKU is available for purchase.
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return ($this->flags & self::FLAG_AVAILABLE) === self::FLAG_AVAILABLE;
    }

    /**
     * Checks if the SKU is a guild subscription.
     *
     * @return bool
     */
    public function isGuildSubscription(): bool
    {
        return ($this->flags & self::FLAG_GUILD_SUBSCRIPTION) === self::FLAG_GUILD_SUBSCRIPTION;
    }

    /**
     * Checks if the SKU is a user subscription.
     *
     * @return bool
     */
    public function isUserSubscription(): bool
    {
        return ($this->flags & self::FLAG_USER_SUBSCRIPTION) === self::FLAG_USER_SUBSCRIPTION;
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'sku_id' => $this->id,
            'application_id' => $this->application_id,
        ];
    }
}
