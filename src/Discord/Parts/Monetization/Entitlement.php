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

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Repository\Monetization\EntitlementRepository;
use React\Promise\PromiseInterface;

use function React\Promise\reject;

/**
 * Entitlements in Discord represent that a user or guild has access to a premium offering in your application.
 *
 * @link https://discord.com/developers/docs/resources/entitlement#entitlement-object
 *
 * @since 10.15.0
 *
 * @property string        $id             ID of the entitlement.
 * @property string        $sku_id         ID of the SKU.
 * @property string        $application_id ID of the parent application.
 * @property string|null   $user_id        ID of the user that is granted access to the entitlement's SKU.
 * @property int           $type           Type of entitlement.
 * @property bool          $deleted        Whether the entitlement was deleted.
 * @property ?Carbons|null $starts_at      Start date at which the entitlement is valid (ISO8601 timestamp).
 * @property ?Carbon|null  $ends_at        Date at which the entitlement is no longer valid (ISO8601 timestamp).
 * @property string|null   $guild_id       ID of the guild that is granted access to the entitlement's SKU.
 * @property bool|null     $consumed       For consumable items, whether or not the entitlement has been consumed.
 */
class Entitlement extends Part
{
    /** Entitlement was purchased by user. */
    public const PURCHASE = 1;
    /** Entitlement for Discord Nitro subscription. */
    public const PREMIUM_SUBSCRIPTION = 2;
    /** Entitlement was gifted by developer. */
    public const DEVELOPER_GIFT = 3;
    /** Entitlement was purchased by a dev in application test mode. */
    public const TEST_MODE_PURCHASE = 4;
    /** Entitlement was granted when the SKU was free. */
    public const FREE_PURCHASE = 5;
    /** Entitlement was gifted by another user. */
    public const USER_GIFT = 6;
    /** Entitlement was claimed by user for free as a Nitro Subscriber. */
    public const PREMIUM_PURCHASE = 7;
    /** Entitlement was purchased as an app subscription. */
    public const APPLICATION_SUBSCRIPTION = 8;

    /**
     * @inheritdoc
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
        return $this->attributeCarbonHelper('starts_at');
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
        return $this->attributeCarbonHelper('ends_at');
    }

    /**
     * Gets the originating repository of the part.
     *
     * @since 10.42.0
     *
     * @throws \Exception If the part does not have an originating repository.
     *
     * @return EntitlementRepository The repository.
     */
    public function getRepository(): EntitlementRepository
    {
        return $this->discord->application->entitlements;
    }

    /**
     * @inheritdoc
     */
    public function save(?string $reason = null): PromiseInterface
    {
        if (isset($this->attributes['id']) || $this->created) {
            return reject(new \DomainException('Entitlements cannot be modified once created.'));
        }

        if (! isset($this->attributes['user_id']) && ! isset($this->attributes['guild_id'])) {
            return reject(new \DomainException('Entitlements must have either a user_id or guild_id.'));
        }

        $data = ['sku_id' => (string) $this->attributes['sku_id']];
        $data['owner_id'] = (string) $this->attributes['user_id'] ?? (string) $this->attributes['guild_id'];
        $data['owner_type'] = isset($this->attributes['user_id']) ? 'user' : 'guild';

        return $this->discord->application->entitlements->createTestEntitlement($data);
    }
}
