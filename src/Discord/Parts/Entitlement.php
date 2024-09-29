<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts;

use Carbon\Carbon;
use Discord\Http\Endpoint;
use Discord\Parts\Part;
use Discord\Parts\User\User;
use Discord\Parts\Guild\Guild;
use React\Promise\ExtendedPromiseInterface;

/**
 * An entitlement object represents a premium offering in the application that a user or guild has access to.
 *
 * @link https://discord.com/developers/docs/resources/entitlement
 *
 * @since 10.0.0
 *
 * @property string           $id              ID of the entitlement.
 * @property string           $sku_id          ID of the SKU.
 * @property string           $application_id  ID of the parent application.
 * @property string|null      $user_id         ID of the user that is granted access to the entitlement's SKU.
 * @property int              $type            Type of entitlement.
 * @property bool             $deleted         Entitlement was deleted.
 * @property Carbon|null      $starts_at       Start date at which the entitlement is valid.
 * @property Carbon|null      $ends_at         Date at which the entitlement is no longer valid.
 * @property string|null      $guild_id        ID of the guild that is granted access to the entitlement's SKU.
 * @property bool|null        $consumed        For consumable items, whether or not the entitlement has been consumed.
 * @property-read Guild|null  $guild           The guild that is granted access to the entitlement's SKU.
 * @property-read User|null   $user            The user that is granted access to the entitlement's SKU.
 */
class Entitlement extends Part
{
    public const OWNER_TYPE_GUILD = 1;
    public const OWNER_TYPE_USER = 2;

    public const TYPE_PURCHASE = 1;
    public const TYPE_PREMIUM_SUBSCRIPTION = 2;
    public const TYPE_DEVELOPER_GIFT = 3;
    public const TYPE_TEST_MODE_PURCHASE = 4;
    public const TYPE_FREE_PURCHASE = 5;
    public const TYPE_USER_GIFT = 6;
    public const TYPE_PREMIUM_PURCHASE = 7;
    public const TYPE_APPLICATION_SUBSCRIPTION = 8;

    /**
     * {@inheritDoc}
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

        // @internal
        'guild',
        'user',
    ];

    /**
     * Returns the guild attribute that is granted access to the entitlement's sku.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the user that is granted access to the entitlement's sku.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

     /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'application_id' => $this->application_id,
            'entitlement_id' => $this->id,
        ];
    }

     /**
     * Returns the starts at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getStartsAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['starts_at'])) {
            return null;
        }

        return new Carbon($this->attributes['starts_at']);
    }

    /**
     * Returns the ends at attribute.
     *
     * @return Carbon|null The time that the invite was created.
     *
     * @throws \Exception
     */
    protected function getEndsAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['ends_at'])) {
            return null;
        }

        return new Carbon($this->attributes['ends_at']);
    }

    /**
     * Consumes an entitlement.
     *
     * For One-Time Purchase consumable SKUs, marks a given entitlement for the user as consumed.
     * The entitlement will have consumed: true when using List Entitlements.
     *
     * @param Entitlement $part
     *
     * @return ExtendedPromiseInterface A promise that resolves to an Entitlement object.
     */
    public function consume(Entitlement $part): ExtendedPromiseInterface
    {
        return $this->http->post(Endpoint::bind(Endpoint::APPLICATION_ENTITLEMENT_CONSUME, $this->application_id, $part->id))
            ->then(function ($response) use ($part) { // Returns a 204 No Content on success.
                $part->consumed = true;
                return $part;
            });
    }
}
