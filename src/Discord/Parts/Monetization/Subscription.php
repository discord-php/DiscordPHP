<?php

declare(strict_types=1);

namespace Discord\Parts\Monetization;

use Carbon\Carbon;
use Discord\Parts\Part;

/**
 * Subscriptions in Discord represent a user making recurring payments for at least one SKU over an ongoing period.
 *
 * Subscription status should not be used to grant perks.
 * Use entitlements as an indication of whether a user should have access to a specific SKU.
 * See the guide on Implementing App Subscriptions for more information.
 *
 * @link https://discord.com/developers/docs/monetization/implementing-app-subscriptions
 *
 * @link https://discord.com/developers/docs/resources/subscription
 *
 * @since 10.15.0
 *
 * @property string      $id                   ID of the subscription.
 * @property string      $user_id              ID of the user who is subscribed.
 * @property array       $sku_ids              List of SKUs subscribed to.
 * @property array       $entitlement_ids      List of entitlements granted for this subscription.
 * @property array|null  $renewal_sku_ids      List of SKUs that this user will be subscribed to at renewal.
 * @property Carbon      $current_period_start Start of the current subscription period.
 * @property Carbon      $current_period_end   End of the current subscription period.
 * @property int         $status               Current status of the subscription.
 * @property Carbon|null $canceled_at          When the subscription was canceled.
 * @property string|null $country              ISO3166-1 alpha-2 country code of the payment source used to purchase the subscription. Missing unless queried with a private OAuth scope.
 */
class Subscription extends Part
{
    // Subscription Statuses
    public const STATUS_ACTIVE = 0;
    public const STATUS_ENDING = 1;
    public const STATUS_INACTIVE = 2;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'user_id',
        'sku_ids',
        'entitlement_ids',
        'renewal_sku_ids',
        'current_period_start',
        'current_period_end',
        'status',
        'canceled_at',
        'country',
    ];

    /**
     * Get the start of the current subscription period as a Carbon instance.
     *
     * @return Carbon|null
     */
    protected function getCurrentPeriodStartAttribute(): ?Carbon
    {
        if (! isset($this->attributes['current_period_start'])) {
            return null;
        }

        return Carbon::parse($this->attributes['current_period_start']);
    }

    /**
     * Get the end of the current subscription period as a Carbon instance.
     *
     * @return Carbon|null
     */
    protected function getCurrentPeriodEndAttribute(): ?Carbon
    {
        if (! isset($this->attributes['current_period_end'])) {
            return null;
        }

        return Carbon::parse($this->attributes['current_period_end']);
    }

    /**
     * Get the canceled_at timestamp as a Carbon instance.
     *
     * @return Carbon|null
     */
    protected function getCanceledAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['canceled_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['canceled_at']);
    }
}
