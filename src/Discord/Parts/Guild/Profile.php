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

namespace Discord\Parts\Guild;

use Discord\Helpers\ExCollectionInterface;
use Discord\Parts\Part;

/**
 * This object can only be retrieved through the `profile` field of the [GET Invite](/docs/resources/invite#get-invite) endpoint.
 *
 * @link https://discord.com/developers/docs/resources/guild#guild-profile-object
 *
 * @since 10.22.0
 *
 * @property string                                                     $id                         The unique identifier of the guild.
 * @property int                                                        $badge                      Guild badge.
 * @property string                                                     $badge_color_primary        Primary color for the guild badge.
 * @property string                                                     $badge_color_secondary      Secondary color for the guild badge.
 * @property string|null                                                $badge_hash                 Guild tag badge hash.
 * @property string|null                                                $banner_hash                Server tag badge hash.
 * @property string|null                                                $custom_banner_hash         Custom banner hash.
 * @property string|null                                                $description                The description for the guild.
 * @property array                                                      $features                   Enabled guild features.
 * @property object                                                     $game_activity              Game activity data for the guild.
 * @property array                                                      $game_application_ids       Application ids for games associated with the guild.
 * @property string|null                                                $icon_hash                  Icon hash.
 * @property int                                                        $member_count               Total number of members in the guild.
 * @property string                                                     $name                       Guild name.
 * @property int                                                        $online_count               Number of online members in the guild.
 * @property int                                                        $premium_subscription_count The number of boosts this guild currently has.
 * @property int                                                        $premium_tier               Premium tier (Server Boost level).
 * @property string|null                                                $tag                        Tag of the guild.
 * @property ExCollectionInterface<GuildTraitObject>|GuildTraitObject[] $traits                     Traits of the guild.
 * @property int                                                        $visibility                 Visibility level of the guild.
 */
class Profile extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'badge',
        'badge_color_primary',
        'badge_color_secondary',
        'badge_hash',
        'banner_hash',
        'custom_banner_hash',
        'description',
        'features',
        'game_activity',
        'game_application_ids',
        'icon_hash',
        'member_count',
        'name',
        'online_count',
        'premium_subscription_count',
        'premium_tier',
        'tag',
        'traits',
        'visibility',
    ];

    /**
     * Returns the traits attribute.
     *
     * @return ExCollectionInterface<GuildTraitObject>|GuildTraitObject[] A collection of guild trait objects.
     */
    protected function getTraitsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('traits', GuildTraitObject::class, 'emoji_id');
    }
}
