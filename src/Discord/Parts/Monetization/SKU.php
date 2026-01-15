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
 * @property string $slug           System-generated URL slug.
 * @property int    $flags          SKU flags as a bitfield.
 *
 * @property-read SubscriptionRepository $subscriptions Subscriptions related to this SKU.
 */
class SKU extends Part
{
    /** Durable one-time purchase. */
    public const TYPE_DURABLE = 2;
    /** Consumable one-time purchase. */
    public const TYPE_CONSUMABLE = 3;
    /** Represents a recurring subscription. */
    public const TYPE_SUBSCRIPTION = 5;
    /** System-generated group for each SUBSCRIPTION SKU created. */
    public const TYPE_SUBSCRIPTION_GROUP = 6;

    /** SKU is available for purchase. */
    public const FLAG_AVAILABLE = 1 << 2;
    /** Recurring SKU that can be purchased by a user and applied to a single server. Grants access to every user in that server. */
    public const FLAG_GUILD_SUBSCRIPTION = 1 << 7;
    /** Recurring SKU purchased by a user for themselves. Grants access to the purchasing user in every server. */
    public const FLAG_USER_SUBSCRIPTION = 1 << 8;

    /**
     * @inheritdoc
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
     * @inheritDoc
     */
    protected $repositories = [
        'subscriptions' => SubscriptionRepository::class,
    ];
}
