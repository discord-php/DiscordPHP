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

use Carbon\Carbon;
use Discord\Helpers\Collection;
use Discord\Parts\Part;
use Discord\Repository\EntitlementRepository;
use Discord\Repository\SKUsRepository;
use React\Promise\ExtendedPromiseInterface;

/**
 * A Subscription object represents a user making recurring payments for at least one SKU over an ongoing period.
 *
 * @link https://discord.com/developers/docs/resources/subscription
 *
 * @since 10.0.0
 *
 * @property string                      $id                    ID of the subscription.
 * @property string                      $user_id               ID of the user who is subscribed.
 * @property array                       $sku_ids               List of SKUs subscribed to.
 * @property array                       $entitlement_ids       List of entitlements granted for this subscription.
 * @property Carbon                      $current_period_start  Start of the current subscription period.
 * @property Carbon                      $current_period_end    End of the current subscription period.
 * @property int                         $status                Current status of the subscription.
 * @property Carbon|null                 $canceled_at           When the subscription was canceled.
 * @property string|null                 $country               ISO3166-1 alpha-2 country code of the payment source used to purchase the subscription.
 * @property-read User                   $user                  User who is subscribed.
 * @property-read Collection             $entitlements          Entitlements granted for this subscription.
 * @property-read Collection             $skus                  SKUs subscribed to.
 */
class Subscription extends Part
{
    public const STATUS_ACTIVE = 0;
    public const STATUS_ENDING = 1;
    public const STATUS_INACTIVE = 2;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'user_id',
        'sku_ids',
        'entitlement_ids',
        'current_period_start',
        'current_period_end',
        'status',
        'canceled_at',
        'country',
    ];

    /**
     * Checks if the subscription is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Checks if the subscription is ending.
     *
     * @return bool
     */
    public function isEnding(): bool
    {
        return $this->status === self::STATUS_ENDING;
    }

    /**
     * Checks if the subscription is inactive.
     *
     * @return bool
     */
    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }

    /**
     * Gets the entitlements for this subscription.
     *
     * @return ExtendedPromiseInterface
     */
    protected function getEntitlementsAttribute(): Collection
    {
        return $this->discord->entitlements->filter(function ($entitlement) {
            return in_array($entitlement->id, $this->entitlement_ids);
        });
    }

    /**
     * Gets the SKUs for this subscription.
     *
     * @return ExtendedPromiseInterface
     */
    protected function getSkusAttribute(): Collection
    {
        return $this->discord->skus->filter(function ($sku) {
            return in_array($sku->id, $this->sku_ids);
        });

        return $repository;
    }

    /**
     * Gets the start of the current subscription period.
     *
     * @return Carbon
     */
    protected function getCurrentPeriodStartAttribute(): Carbon
    {
        return new Carbon($this->attributes['current_period_start']);
    }

    /**
     * Gets the end of the current subscription period.
     *
     * @return Carbon
     */
    protected function getCurrentPeriodEndAttribute(): Carbon
    {
        return new Carbon($this->attributes['current_period_end']);
    }

    /**
     * Gets the cancellation time of the subscription.
     *
     * @return Carbon|null
     */
    protected function getCanceledAtAttribute(): ?Carbon
    {
        return isset($this->attributes['canceled_at']) ? new Carbon($this->attributes['canceled_at']) : null;
    }

    /**
     * Gets the user who is subscribed.
     *
     * @return ExtendedPromiseInterface
     */
    protected function getUserAttribute(): ExtendedPromiseInterface
    {
        return $this->discord->users->cacheGet($this->user_id);
    }
}
