<?php

declare(strict_types=1);

namespace Discord\Parts\Monetization;

use Discord\Parts\Part;
use Discord\Repository\Monetization\SubscriptionRepository;

/**
 * SKUs (stock-keeping units) in Discord represent premium offerings that can be made available to your application's users or guilds.
 *
 * @link https://discord.com/developers/docs/resources/sku
 *
 * @since 10.15.0
 *
 * @property string $id             ID of the SKU.
 * @property int    $type           Type of SKU.
 * @property string $application_id ID of the parent application.
 * @property string $name           Customer-facing name of the premium offering.
 * @property string slug            System-generated URL slug.
 * @property int    $flags          SKU flags as a bitfield.
 *
 * @property-read SubscriptionRepository $subscriptions Subscriptions related to this SKU.
 */
class SKU extends Part
{
    // SKU Types
    public const DURABLE = 2;
    public const CONSUMABLE = 3;
    public const SUBSCRIPTION = 5;
    public const SUBSCRIPTION_GROUP = 6;

    // SKU Flags
    public const AVAILABLE = 1 << 2;
    public const GUILD_SUBSCRIPTION = 1 << 7;
    public const USER_SUBSCRIPTION = 1 << 8;

    /**
     * {@inheritdoc}
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
}
