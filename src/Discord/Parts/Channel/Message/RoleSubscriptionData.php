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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * Data of the role subscription purchase or renewal that prompted this ROLE_SUBSCRIPTION_PURCHASE message.
 *
 * @link https://discord.com/developers/docs/resources/message#role-subscription-data-object
 *
 * @since 10.22.0
 *
 * @property string $role_subscription_listing_id The id of the sku and listing that the user is subscribed to.
 * @property string $tier_name                    The name of the tier that the user is subscribed to.
 * @property int    $total_months_subscribed      The cumulative number of months that the user has been subscribed for.
 * @property bool   $is_renewal                   Whether this notification is for a renewal rather than a new purchase.
 */
class RoleSubscriptionData extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'role_subscription_listing_id',
        'tier_name',
        'total_months_subscribed',
        'is_renewal',
    ];
}
