<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Interactions;

use Discord\Http\Endpoint;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;


/**
 * Represents an entitlement from Discord.
 *
 * @link https://discord.com/developers/docs/monetization/entitlements#entitlement-object
 *
 * @since 10.0.0
 *
 * @property string      $id              ID of the entitlement.
 * @property string      $sku_id          ID of the SKU.
 * @property string      $application_id  ID of the parent application.
 * @property string|null $user_id         ID of the user that is granted access to the entitlement's sku.
 * @property string|null $promotion_id    ID of the promotion that applies to the entitlement.
 * @property-read User|null $user         User that is granted access to the entitlement's sku.
 * @property int         $type            Type of entitlement.
 * @property bool        $deleted         Entitlement was deleted.
 * @property int|null    $gift_code_flags Gift code flags that apply to this entitlement.
 * @property bool|null   $consumed        Entitlement has been consumed.
 * @property Carbon|null $starts_at       Start date at which the entitlement is valid. Not present when using test entitlements.
 * @property Carbon|null $ends_at         Date at which the entitlement is no longer valid. Not present when using test entitlements.
 * @property string|null $guild_id        ID of the guild that is granted access to the entitlement's sku.
 * @property-read Guild|null $guild       Guild that is granted access to the entitlement's sku.
 * @property string|null $subscription_id ID of the subscription.
 */
class Entitlement extends Part
{
    public const TYPE_APPLICATION_SUBSCRIPTION = 8;


    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'sku_id',
        'application_id',
        'user_id',
        'promotion_id',
        'type',
        'deleted',
        'gift_code_flags',
        'consumed',
        'guild_id',
        'subscription_id',
        'starts_at',
        'ends_at',
    ];

    /**
     * Returns the guild that is granted access to the entitlement's sku.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the user that is granted access to the entitlement's sku.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Returns the start date at which the entitlement is valid. Not present when using test entitlements.
     *
     * @return Carbon|null The start date at which the entitlement is valid. Not present when using test entitlements.
     *
     * @throws \Exception
     */
    protected function getStartsAtAttribute(): ?Carbon
    {
        if ($this->starts_at === null) {
            return null;
        }

        return new Carbon($this->starts_at);
    }

    /**
     * Returns the date at which the entitlement is no longer valid. Not present when using test entitlements.
     *
     * @return Carbon|null The date at which the entitlement is no longer valid. Not present when using test entitlements.
     *
     * @throws \Exception
     */
    protected function getEndsAtAttribute(): ?Carbon
    {
        if ($this->ends_at === null) {
            return null;
        }

        return new Carbon($this->ends_at);
    }
}
