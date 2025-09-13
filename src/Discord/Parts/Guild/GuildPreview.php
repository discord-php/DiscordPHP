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
use Discord\Repository\Guild\EmojiRepository;
use Discord\Repository\Guild\StickerRepository;

/**
 * A guild can be previewed before joining via Membership Screening or the directory.
 *
 * @link https://discord.com/developers/docs/resources/guild#guild-preview-object-guild-preview-structure
 *
 * @property      string            $id                                                The unique identifier of the guild.
 * @property      string            $name                                              The name of the guild.
 * @property      ?string           $icon                                              The URL to the guild icon.
 * @property      ?string|null      $icon_hash                                         The icon hash for the guild.
 * @property      ?string           $splash                                            The URL to the guild splash.
 * @property      ?string|null      $splash_hash                                       The splash hash for the guild.
 * @property      ?string           $discovery_splash                                  Discovery splash hash. Only for discoverable guilds.
 * @property      EmojiRepository   $emojis                                            Custom guild emojis.
 * @property      string[]          $features                                          An array of features that the guild has.
 * @property-read bool              $feature_animated_banner                           Guild has access to set an animated guild banner image.
 * @property-read bool              $feature_animated_icon                             Guild has access to set an animated guild icon.
 * @property-read bool              $feature_application_command_permissions_v2        Guild is using the old permissions configuration behavior.
 * @property-read bool              $feature_auto_moderation                           Guild has set up auto moderation rules.
 * @property-read bool              $feature_banner                                    Guild has access to set a guild banner image.
 * @property-read bool              $feature_community                                 Guild can enable welcome screen, Membership Screening, stage channels and discovery, and receives community updates.
 * @property-read bool              $feature_creator_monetizable_provisional           Guild has enabled monetization.
 * @property-read bool              $feature_creator_store_page                        Guild has enabled the role subscription promo page.
 * @property-read bool              $feature_developer_support_server                  Guild has been set as a support server on the App Directory.
 * @property-read bool              $feature_discoverable                              Guild is able to be discovered in the directory.
 * @property-read bool              $feature_featurable                                Guild is able to be featured in the directory.
 * @property-read bool              $feature_has_directory_entry                       Guild is listed in a directory channel.
 * @property-read bool              $feature_invites_disabled                          Guild has paused invites, preventing new users from joining.
 * @property-read bool              $feature_invite_splash                             Guild has access to set an invite splash background.
 * @property-read bool              $feature_linked_to_hub                             Guild is in a Student Hub.
 * @property-read bool              $feature_member_verification_gate_enabled          Guild has enabled membership screening.
 * @property-read bool              $feature_monetization_enabled                      Guild has enabled monetization.
 * @property-read bool              $feature_more_soundboard                           Guild has increased custom soundboard sound slots.
 * @property-read bool              $feature_more_stickers                             Guild has increased custom sticker slots.
 * @property-read bool              $feature_news                                      Guild has access to create news channels.
 * @property-read bool              $feature_partnered                                 Guild is partnered.
 * @property-read bool              $feature_preview_enabled                           Guild can be previewed before joining via membership screening or the directory.
 * @property-read bool              $feature_private_threads                           Guild has access to create private threads.
 * @property-read bool              $feature_raid_alerts_enabled                       Guild has enabled alerts for join raids in the configured safety alerts channel.
 * @property-read bool              $feature_raid_alerts_disabled                      Guild has disabled alerts for join raids in the configured safety alerts channel.
 * @property-read bool              $feature_role_icons                                Guild is able to set role icons.
 * @property-read bool              $feature_role_subscriptions_available_for_purchase Guild has role subscriptions that can be purchased.
 * @property-read bool              $feature_role_subscriptions_enabled                Guild has enabled role subscriptions.
 * @property-read bool              $feature_soundboard                                Guild has created soundboard sounds.
 * @property-read bool              $feature_ticketed_events_enabled                   Guild has enabled ticketed events.
 * @property-read bool              $feature_vanity_url                                Guild has access to set a vanity url.
 * @property-read bool              $feature_verified                                  Guild is verified.
 * @property-read bool              $feature_vip_regions                               Guild has access to set 384kbps bitrate in voice.
 * @property-read bool              $feature_welcome_screen_enabled                    Guild has enabled the welcome screen.
 * @property-read bool              $feature_guests_enabled                            Guild has access to guest invites.
 * @property-read bool              $feature_enhanced_role_colors                      Guild is able to set gradient colors to roles.
 * @property      int|null          $approximate_member_count                          Approximate number of members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property      int|null          $approximate_presence_count                        Approximate number of non-offline members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property      ?string           $description                                       The description of the guild.
 * @property      StickerRepository $stickers                                          Custom guild stickers.
 */
class GuildPreview extends Part
{
    use GuildTrait;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'splash',
        'discovery_splash',
        'emojis',
        'features',
        'approximate_member_count',
        'approximate_presence_count',
        'description',
        'stickers',
    ];

    /**
     * @inheritDoc
     */
    protected $visible = [
        'feature_animated_banner',
        'feature_animated_icon',
        'feature_application_command_permissions_v2',
        'feature_auto_moderation',
        'feature_banner',
        'feature_community',
        'feature_creator_monetizable_provisional',
        'feature_creator_store_page',
        'feature_developer_support_server',
        'feature_discoverable',
        'feature_featurable',
        'feature_has_directory_entry',
        'feature_invites_disabled',
        'feature_invite_splash',
        'feature_linked_to_hub',
        'feature_member_verification_gate_enabled',
        'feature_more_soundboard',
        'feature_more_stickers',
        'feature_monetization_enabled',
        'feature_more_stickers',
        'feature_news',
        'feature_partnered',
        'feature_preview_enabled',
        'feature_private_threads',
        'feature_raid_alerts_enabled',
        'feature_raid_alerts_disabled',
        'feature_role_icons',
        'feature_role_subscriptions_available_for_purchase',
        'feature_role_subscriptions_enabled',
        'feature_soundboard',
        'feature_ticketed_events_enabled',
        'feature_vanity_url',
        'feature_verified',
        'feature_vip_regions',
        'feature_welcome_screen_enabled',
        'feature_guest_enabled',
        'feature_enhanced_role_colors',
    ];

    /**
     * @inheritDoc
     */
    protected $repositories = [
        'emojis' => EmojiRepository::class,
        'stickers' => StickerRepository::class,
    ];
}
