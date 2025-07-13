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

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Stringable;

/**
 * Role tags for a Discord role.
 *
 * Tags with type null represent booleans. They will be present and set to null if they are "true", and will be not present if they are "false".
 * The library currently overrides this behavior to return true if the property is present at all.
 *
 * @link https://discord.com/developers/docs/topics/permissions#role-object-role-tags-structure
 *
 * @since 10.19.0
 *
 * @property ?string|null $bot_id                  The id of the bot this role belongs to.
 * @property ?string|null $integration_id          The id of the integration this role belongs to.
 * @property bool         $premium_subscriber      Whether this is the guild's Booster role.
 * @property ?string|null $subscription_listing_id The id of this role's subscription sku and listing.
 * @property bool         $available_for_purchase  Whether this role is available for purchase.
 * @property bool         $guild_connections       Whether this role is a guild's linked role.
 */
class RoleTags extends Part implements Stringable
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'bot_id',
        'integration_id',
        'premium_subscriber',
        'subscription_listing_id',
        'available_for_purchase',
        'guild_connections',
    ];

    /**
     * Gets the premium subscriber attribute.
     *
     * @return true|null
     */
    protected function getPremiumSubscriberAttribute(): ?true
    {
        return array_key_exists('premium_subscriber', $this->attributes) ?: null;
    }

    /**
     * Gets the available for purchase attribute.
     *
     * @return true|null
     */
    protected function getAvailableForPurchaseAttribute(): ?true
    {
        return array_key_exists('available_for_purchase', $this->attributes) ?: null;
    }

    /**
     * Gets the guild connections attribute.
     *
     * @return true|null
     */
    protected function getGuildConnectionsAttribute(): ?true
    {
        return array_key_exists('guild_connections', $this->attributes) ?: null;
    }
}
