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

namespace Discord\Parts\Monetization;

use Carbon\Carbon;
use Discord\Parts\Part;

/**
 * Entitlements in Discord represent that a user or guild has access to a premium offering in your application.
 *
 * @link https://discord.com/developers/docs/resources/entitlement#entitlement-object
 *
 * @since 10.15.0
 *
 * @property string       $id             ID of the entitlement.
 * @property string       $sku_id         ID of the SKU.
 * @property string       $application_id ID of the parent application.
 * @property string|null  $user_id        ID of the user that is granted access to the entitlement's SKU.
 * @property int          $type           Type of entitlement.
 * @property bool         $deleted        Whether the entitlement was deleted.
 * @property Carbons|null $starts_at      Start date at which the entitlement is valid (ISO8601 timestamp).
 * @property Carbon|null  $ends_at        Date at which the entitlement is no longer valid (ISO8601 timestamp).
 * @property string|null  $guild_id       ID of the guild that is granted access to the entitlement's SKU.
 * @property bool|null    $consumed       For consumable items, whether or not the entitlement has been consumed.
 */
class Entitlement extends Part
{
    public const PURCHASE = 1;
    public const PREMIUM_SUBSCRIPTION = 2;
    public const DEVELOPER_GIFT = 3;
    public const TEST_MODE_PURCHASE = 4;
    public const FREE_PURCHASE = 5;
    public const USER_GIFT = 6;
    public const PREMIUM_PURCHASE = 7;
    public const APPLICATION_SUBSCRIPTION = 8;

    /**
     * {@inheritdoc}
     */
    protected $fillable = [
        'id',
        'sku_id',
        'application_id',
        'user_id',
        'type',
        'deleted',
        'starts_at',
        'ends_at',
        'guild_id',
        'consumed',
    ];

    /**
     * Return the starts_at attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getStartsAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['starts_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['starts_at']);
    }

    /**
     * Return the ends_at attribute.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getEndsAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['ends_at'])) {
            return null;
        }

        return Carbon::parse($this->attributes['ends_at']);
    }
}
