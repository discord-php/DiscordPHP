<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Exceptions\FileNotFoundException;
use Discord\Helpers\Collection;
use Discord\Helpers\Multipart;
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Channel\StageInstance;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Repository\Guild\BanRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\EmojiRepository;
use Discord\Repository\Guild\InviteRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\Parts\Guild\AuditLog\Entry;
use Discord\Parts\Permissions\RolePermission;
use Discord\Repository\Guild\AutoModerationRuleRepository;
use Discord\Repository\Guild\CommandPermissionsRepository;
use Discord\Repository\Guild\GuildCommandRepository;
use Discord\Repository\Guild\StickerRepository;
use Discord\Repository\Guild\ScheduledEventRepository;
use Discord\Repository\Guild\GuildTemplateRepository;
use Discord\Repository\Guild\IntegrationRepository;
use React\Promise\ExtendedPromiseInterface;
use ReflectionClass;
use Symfony\Component\OptionsResolver\OptionsResolver;

use function Discord\normalizePartId;
use function Discord\poly_strlen;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members,
 * Channels, Roles, Bans etc.
 *
 * @link https://discord.com/developers/docs/resources/guild
 *
 * @since 2.0.0 Refactored as Part
 * @since 1.0.0
 *
 * @property      string             $id                                                The unique identifier of the guild.
 * @property      string             $name                                              The name of the guild.
 * @property      ?string            $icon                                              The URL to the guild icon.
 * @property      ?string|null       $icon_hash                                         The icon hash for the guild.
 * @property      ?string            $splash                                            The URL to the guild splash.
 * @property      ?string|null       $splash_hash                                       The splash hash for the guild.
 * @property      ?string            $discovery_splash                                  Discovery splash hash. Only for discoverable guilds.
 * @property      string             $owner_id                                          The unique identifier of the owner of the guild.
 * @property-read User|null          $owner                                             The owner of the guild.
 * @property      string             $afk_channel_id                                    The unique identifier of the AFK channel ID.
 * @property      int                $afk_timeout                                       How long in seconds you will remain in the voice channel until you are moved into the AFK channel. Can be set to: 60, 300, 900, 1800, 3600.
 * @property      bool|null          $widget_enabled                                    Is server widget enabled.
 * @property      ?string|null       $widget_channel_id                                 Channel that the widget will create an invite to.
 * @property      int                $verification_level                                The verification level used for the guild.
 * @property      int                $default_message_notifications                     Default notification level.
 * @property      int                $explicit_content_filter                           Explicit content filter level.
 * @property      RoleRepository     $roles                                             Roles in the guild.
 * @property      EmojiRepository    $emojis                                            Custom guild emojis.
 * @property      string[]           $features                                          An array of features that the guild has.
 * @property-read bool               $feature_animated_banner                           Guild has access to set an animated guild banner image.
 * @property-read bool               $feature_animated_icon                             Guild has access to set an animated guild icon.
 * @property-read bool               $feature_application_command_permissions_v2        Guild is using the old permissions configuration behavior.
 * @property-read bool               $feature_auto_moderation                           Guild has set up auto moderation rules.
 * @property-read bool               $feature_banner                                    Guild has access to set a guild banner image.
 * @property-read bool               $feature_community                                 Guild can enable welcome screen, Membership Screening, stage channels and discovery, and receives community updates.
 * @property-read bool               $feature_creator_monetizable_provisional           Guild has enabled monetization.
 * @property-read bool               $feature_creator_store_page                        Guild has enabled the role subscription promo page.
 * @property-read bool               $feature_developer_support_server                  Guild has been set as a support server on the App Directory.
 * @property-read bool               $feature_discoverable                              Guild is able to be discovered in the directory.
 * @property-read bool               $feature_featurable                                Guild is able to be featured in the directory.
 * @property-read bool               $feature_has_directory_entry                       Guild is listed in a directory channel.
 * @property-read bool               $feature_invites_disabled                          Guild has paused invites, preventing new users from joining.
 * @property-read bool               $feature_invite_splash                             Guild has access to set an invite splash background.
 * @property-read bool               $feature_linked_to_hub                             Guild is in a Student Hub.
 * @property-read bool               $feature_member_verification_gate_enabled          Guild has enabled membership screening.
 * @property-read bool               $feature_monetization_enabled                      Guild has enabled monetization.
 * @property-read bool               $feature_more_stickers                             Guild has increased custom sticker slots.
 * @property-read bool               $feature_news                                      Guild has access to create news channels.
 * @property-read bool               $feature_partnered                                 Guild is partnered.
 * @property-read bool               $feature_preview_enabled                           Guild can be previewed before joining via membership screening or the directory.
 * @property-read bool               $feature_private_threads                           Guild has access to create private threads.
 * @property-read bool               $feature_raid_alerts_enabled                       Guild has enabled alerts for join raids in the configured safety alerts channel.
 * @property-read bool               $feature_role_icons                                Guild is able to set role icons.
 * @property-read bool               $feature_role_subscriptions_available_for_purchase Guild has role subscriptions that can be purchased.
 * @property-read bool               $feature_role_subscriptions_enabled                Guild has enabled role subscriptions.
 * @property-read bool               $feature_ticketed_events_enabled                   Guild has enabled ticketed events.
 * @property-read bool               $feature_vanity_url                                Guild has access to set a vanity url.
 * @property-read bool               $feature_verified                                  Guild is verified.
 * @property-read bool               $feature_vip_regions                               Guild has access to set 384kbps bitrate in voice.
 * @property-read bool               $feature_welcome_screen_enabled                    Guild has enabled the welcome screen.
 * @property      int                $mfa_level                                         MFA level required to join.
 * @property      ?string            $application_id                                    Application that made the guild, if made by one.
 * @property      ?string            $system_channel_id                                 Channel that system notifications are posted in.
 * @property      int                $system_channel_flags                              Flags for the system channel.
 * @property      ?string            $rules_channel_id                                  Channel that the rules are in.
 * @property      int|null           $max_presences                                     Maximum amount of presences allowed in the guild.
 * @property      int|null           $max_members                                       Maximum amount of members allowed in the guild.
 * @property      ?string            $vanity_url_code                                   Vanity URL code for the guild.
 * @property      ?string            $description                                       Guild description of a guild.
 * @property      ?string            $banner                                            Banner hash.
 * @property      int                $premium_tier                                      Server boost level.
 * @property      int|null           $premium_subscription_count                        Number of boosts in the guild.
 * @property      string             $preferred_locale                                  Preferred locale of the guild.
 * @property      ?string            $public_updates_channel_id                         Notice channel id.
 * @property      int|null           $max_video_channel_users                           Maximum amount of users allowed in a video channel.
 * @property      int|null           $max_stage_video_channel_users                     Maximum amount of users in a stage video channel.
 * @property      int|null           $approximate_member_count                          Approximate number of members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property      int|null           $approximate_presence_count                        Approximate number of non-offline members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property-read WelcomeScreen|null $welcome_screen                                    The welcome screen of a Community guild, shown to new members, returned in an Invite's guild object. use `getWelcomeScreen` first to populate.
 * @property      int                $nsfw_level                                        The guild NSFW level.
 * @property      StickerRepository  $stickers                                          Custom guild stickers.
 * @property      bool               $premium_progress_bar_enabled                      Whether the guild has the boost progress bar enabled.
 * @property      string|null        $safety_alerts_channel_id                          The id of the channel where admins and moderators of Community guilds receive safety alerts from Discord.
 *
 * @property Carbon|null              $joined_at              A timestamp of when the current user joined the guild.
 * @property bool|null                $large                  Whether the guild is considered 'large' (over 250 members).
 * @property int|null                 $member_count           How many members are in the guild.
 * @property MemberRepository         $members                Users in the guild.
 * @property ChannelRepository        $channels               Channels in the guild.
 * @property ScheduledeventRepository $guild_scheduled_events The scheduled events in the guild.
 *
 * @property AutoModerationRuleRepository $auto_moderation_rules
 * @property BanRepository                $bans
 * @property GuildCommandRepository       $commands
 * @property CommandPermissionsRepository $command_permissions
 * @property IntegrationRepository        $integrations
 * @property InviteRepository             $invites
 * @property GuildTemplateRepository      $templates
 */
class Guild extends Part
{
    public const REGION_DEFAULT = 'us_west';

    public const NOTIFICATION_ALL_MESSAGES = 0;
    public const NOTIFICATION_ONLY_MENTIONS = 1;

    public const EXPLICIT_CONTENT_FILTER_DISABLED = 0;
    public const EXPLICIT_CONTENT_FILTER_MEMBERS_WITHOUT_ROLES = 1;
    public const EXPLICIT_CONTENT_FILTER_ALL_MEMBERS = 2;

    public const MFA_NONE = 0;
    public const MFA_ELEVATED = 1;

    public const LEVEL_OFF = 0;
    public const LEVEL_LOW = 1;
    public const LEVEL_MEDIUM = 2;
    public const LEVEL_TABLEFLIP = 3;
    public const LEVEL_DOUBLE_TABLEFLIP = 4;

    public const NSFW_DEFAULT = 0;
    public const NSFW_EXPLICIT = 1;
    public const NSFW_SAFE = 2;
    public const NSFW_AGE_RESTRICTED = 3;

    public const PREMIUM_NONE = 0;
    public const PREMIUM_TIER_1 = 1;
    public const PREMIUM_TIER_2 = 2;
    public const PREMIUM_TIER_3 = 3;

    public const SUPPRESS_JOIN_NOTIFICATIONS = (1 << 0);
    public const SUPPRESS_PREMIUM_SUBSCRIPTION = (1 << 1);
    public const SUPPRESS_GUILD_REMINDER_NOTIFICATIONS = (1 << 2);
    public const SUPPRESS_JOIN_NOTIFICATION_REPLIES = (1 << 3);
    public const SUPPRESS_ROLE_SUBSCRIPTION_PURCHASE_NOTIFICATIONS = (1 << 4);
    public const SUPPRESS_ROLE_SUBSCRIPTION_PURCHASE_NOTIFICATION_REPLIES = (1 << 5);

    public const HUB_TYPE_DEFAULT = 0;
    public const HUB_TYPE_HIGH_SCHOOL = 1;
    public const HUB_TYPE_COLLEGE = 2;

    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'name',
        'icon',
        'icon_hash',
        'description',
        'splash',
        'discovery_splash',
        'features',
        'banner',
        'owner_id',
        'application_id',
        'afk_channel_id',
        'afk_timeout',
        'system_channel_id',
        'widget_enabled',
        'widget_channel_id',
        'verification_level',
        'default_message_notifications',
        'hub_type',
        'mfa_level',
        'explicit_content_filter',
        'max_presences',
        'max_members',
        'vanity_url_code',
        'premium_tier',
        'premium_subscription_count',
        'system_channel_flags',
        'preferred_locale',
        'rules_channel_id',
        'public_updates_channel_id',
        'max_video_channel_users',
        'max_stage_video_channel_users',
        'approximate_member_count',
        'approximate_presence_count',
        'welcome_screen',
        'nsfw_level',
        'premium_progress_bar_enabled',
        'safety_alerts_channel_id',

        // events
        'joined_at',
        'large',
        'member_count',

        // repositories
        'channels',
        'roles',
        'emojis',
        'stickers',
    ];

    /**
     * {@inheritDoc}
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
        'feature_monetization_enabled',
        'feature_more_stickers',
        'feature_news',
        'feature_partnered',
        'feature_preview_enabled',
        'feature_private_threads',
        'feature_raid_alerts_enabled',
        'feature_role_icons',
        'feature_role_subscriptions_available_for_purchase',
        'feature_role_subscriptions_enabled',
        'feature_ticketed_events_enabled',
        'feature_vanity_url',
        'feature_verified',
        'feature_vip_regions',
        'feature_welcome_screen_enabled',
    ];

    /**
     * {@inheritDoc}
     */
    protected $repositories = [
        'roles' => RoleRepository::class,
        'emojis' => EmojiRepository::class,
        'stickers' => StickerRepository::class,
        'members' => MemberRepository::class,
        'channels' => ChannelRepository::class,
        'guild_scheduled_events' => ScheduledEventRepository::class,

        'auto_moderation_rules' => AutoModerationRuleRepository::class,
        'bans' => BanRepository::class,
        'commands' => GuildCommandRepository::class,
        'command_permissions' => CommandPermissionsRepository::class,
        'integrations' => IntegrationRepository::class,
        'invites' => InviteRepository::class,
        'templates' => GuildTemplateRepository::class,
    ];

    /**
     * An array of valid regions.
     *
     * @var Collection|null
     */
    protected $regions;

    /**
     * {@inheritDoc}
     */
    protected function setChannelsAttribute(?array $channels): void
    {
        $channelsDiscrim = $this->channels->discrim;
        $clean = array_diff($this->channels->keys(), array_column($channels ?? [], $channelsDiscrim));
        foreach ($channels ?? [] as $channel) {
            $channel = (array) $channel;
            /** @var ?Channel */
            if ($channelPart = $this->channels->offsetGet($channel[$channelsDiscrim])) {
                $channelPart->fill($channel);
            } else {
                /** @var Channel */
                $channelPart = $this->channels->create($channel, $this->created);
                $channelPart->created = &$this->created;
            }
            $this->channels->pushItem($channelPart);
        }

        $this->channels->cache->deleteMultiple($clean);
    }

    /**
     * Sets the roles attribute.
     *
     * @param ?array $roles
     */
    protected function setRolesAttribute(?array $roles): void
    {
        $rolesDiscrim = $this->roles->discrim;
        foreach ($roles ?? [] as $role) {
            $role = (array) $role;
            /** @var ?Role */
            if ($rolePart = $this->roles->offsetGet($role[$rolesDiscrim])) {
                $rolePart->fill($role);
            } else {
                /** @var Role */
                $rolePart = $this->roles->create($role, $this->created);
                $rolePart->created = &$this->created;
            }
            $this->roles->pushItem($rolePart);
        }

        if (! empty($this->attributes['roles']) && $clean = array_diff(array_column($this->attributes['roles'], $rolesDiscrim), array_column($roles ?? [], $rolesDiscrim))) {
            $this->roles->cache->deleteMultiple($clean);
        }

        $this->attributes['roles'] = $roles;
    }

    /**
     * Sets the emojis attribute.
     *
     * @param ?array $emojis
     */
    protected function setEmojisAttribute(?array $emojis): void
    {
        $emojisDiscrim = $this->emojis->discrim;
        foreach ($emojis ?? [] as $emoji) {
            $emoji = (array) $emoji;
            /** @var ?Emoji */
            if ($emojiPart = $this->emojis->offsetGet($emoji[$emojisDiscrim])) {
                $emojiPart->fill($emoji);
            } else {
                /** @var Emoji */
                $emojiPart = $this->emojis->create($emoji, $this->created);
                $emojiPart->created = &$this->created;
            }
            $this->emojis->pushItem($emojiPart);
        }

        if (! empty($this->attributes['emojis']) && $clean = array_diff(array_column($this->attributes['emojis'], $emojisDiscrim), array_column($emojis ?? [], $emojisDiscrim))) {
            $this->emojis->cache->deleteMultiple($clean);
        }

        $this->attributes['emojis'] = $emojis;
    }

    /**
     * Sets the stickers attribute.
     *
     * @param ?array $stickers
     */
    protected function setStickersAttribute(?array $stickers): void
    {
        $stickersDiscrim = $this->stickers->discrim;
        foreach ($stickers ?? [] as $sticker) {
            $sticker = (array) $sticker;
            /** @var ?Sticker */
            if ($stickerPart = $this->stickers->offsetGet($sticker[$stickersDiscrim])) {
                $stickerPart->fill($sticker);
            } else {
                /** @var Sticker */
                $stickerPart = $this->stickers->create($sticker, $this->created);
                $stickerPart->created = &$this->created;
            }
            $this->stickers->pushItem($stickerPart);
        }

        if (! empty($this->attributes['stickers']) && $clean = array_diff(array_column($this->attributes['stickers'], $stickersDiscrim), array_column($stickers ?? [], $stickersDiscrim))) {
            $this->stickers->cache->deleteMultiple($clean);
        }

        $this->attributes['stickers'] = $stickers;
    }

    /**
     * Returns the channels invites.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-invites
     *
     * @throws NoPermissionsException Missing manage_guild permission.
     *
     * @return ExtendedPromiseInterface<Collection|Invite[]>
     */
    public function getInvites(): ExtendedPromiseInterface
    {
        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild) {
            return reject(new NoPermissionsException("You do not have permission to get invites in the guild {$this->id}."));
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_INVITES, $this->id))->then(function ($response) {
            $invites = Collection::for(Invite::class, 'code');

            foreach ($response as $invite) {
                $invite = $this->factory->part(Invite::class, (array) $invite, true);
                $invites->pushItem($invite);
            }

            return $invites;
        });
    }

    /**
     * Unbans a member. Alias for `$guild->bans->unban($user)`.
     *
     * @see BanRepository::unban()
     *
     * @param User|string $user
     *
     * @throws NoPermissionsException Missing ban_members permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function unban($user): ExtendedPromiseInterface
    {
        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->ban_members) {
            return reject(new NoPermissionsException("You do not have permission to ban members in the guild {$this->id}."));
        }

        return $this->bans->unban($user);
    }

    /**
     * Returns the owner.
     *
     * @return User|null
     */
    protected function getOwnerAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->owner_id);
    }

    /**
     * Returns the joined_at attribute.
     *
     * @return Carbon|null The joined_at attribute.
     *
     * @throws \Exception
     */
    protected function getJoinedAtAttribute(): ?Carbon
    {
        if (! isset($this->attributes['joined_at'])) {
            return null;
        }

        return new Carbon($this->attributes['joined_at']);
    }

    /**
     * Returns the guilds icon.
     *
     * @param string|null $format The image format.
     * @param int         $size   The size of the image.
     *
     * @return string|null The URL to the guild icon or null.
     */
    public function getIconAttribute(?string $format = null, int $size = 1024): ?string
    {
        if (! isset($this->attributes['icon'])) {
            return null;
        }

        if (isset($format)) {
            $allowed = ['png', 'jpg', 'webp', 'gif'];

            if (! in_array(strtolower($format), $allowed)) {
                $format = 'webp';
            }
        } elseif (strpos($this->attributes['icon'], 'a_') === 0) {
            $format = 'gif';
        } else {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/icons/{$this->id}/{$this->attributes['icon']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild icon hash.
     *
     * @return string|null The guild icon hash or null.
     */
    protected function getIconHashAttribute(): ?string
    {
        return $this->attributes['icon_hash'] ?? $this->attributes['icon'];
    }

    /**
     * Returns the guild splash.
     *
     * @param string $format The image format.
     * @param int    $size   The size of the image.
     *
     * @return string|null The URL to the guild splash or null.
     */
    public function getSplashAttribute(string $format = 'webp', int $size = 2048): ?string
    {
        if (! isset($this->attributes['splash'])) {
            return null;
        }

        $allowed = ['png', 'jpg', 'webp'];

        if (! in_array(strtolower($format), $allowed)) {
            $format = 'webp';
        }

        return "https://cdn.discordapp.com/splashes/{$this->id}/{$this->attributes['splash']}.{$format}?size={$size}";
    }

    /**
     * Returns the guild splash hash.
     *
     * @return string|null The guild splash hash or null.
     */
    protected function getSplashHashAttribute(): ?string
    {
        return $this->attributes['splash'] ?? null;
    }

    /**
     * Returns the channels stage instances.
     *
     * @deprecated 10.0.0 Use `$channel->stage_instances`
     *
     * @return Collection|StageInstance[]
     */
    protected function getStageInstancesAttribute(): Collection
    {
        $stage_instances = Collection::for(StageInstance::class);

        if ($channels = $this->channels) {
            /** @var Channel */
            foreach ($channels as $channel) {
                $stage_instances->merge($channel->stage_instances);
            }
        }

        return $stage_instances;
    }

    protected function getFeatureAnimatedBannerAttribute(): bool
    {
        return in_array('ANIMATED_BANNER', $this->features);
    }

    protected function getFeatureAnimatedIconAttribute(): bool
    {
        return in_array('ANIMATED_ICON', $this->features);
    }

    protected function getFeatureApplicationCommandPermissionsV2(): bool
    {
        return in_array('APPLICATION_COMMAND_PERMISSIONS_V2', $this->features);
    }

    protected function getFeatureAutoModerationAttribute(): bool
    {
        return in_array('AUTO_MODERATION', $this->features);
    }

    protected function getFeatureBannerAttribute(): bool
    {
        return in_array('BANNER', $this->features);
    }

    protected function getFeatureCommunityAttribute(): bool
    {
        return in_array('COMMUNITY', $this->features);
    }

    protected function getFeatureCreatorMonetizableProvisionalAttribute(): bool
    {
        return in_array('CREATOR_MONETIZABLE_PROVISIONAL', $this->features);
    }

    protected function getFeatureCreatorStorePageAttribute(): bool
    {
        return in_array('CREATOR_STORE_PAGE', $this->features);
    }

    protected function getFeatureDeveloperSupportServerAttribute(): bool
    {
        return in_array('DEVELOPER_SUPPORT_SERVER', $this->features);
    }

    protected function getFeatureDiscoverableAttribute(): bool
    {
        return in_array('DISCOVERABLE', $this->features);
    }

    protected function getFeatureFeaturableAttribute(): bool
    {
        return in_array('FEATURABLE', $this->features);
    }

    protected function getFeatureHasDirectoryEntryAttribute(): bool
    {
        return in_array('HAS_DIRECTORY_ENTRY', $this->features);
    }

    protected function getFeatureInvitesDisabledAttribute(): bool
    {
        return in_array('INVITES_DISABLED', $this->features);
    }

    protected function getFeatureInviteSplashAttribute(): bool
    {
        return in_array('INVITE_SPLASH', $this->features);
    }

    protected function getFeatureLinkedToHubAttribute(): bool
    {
        return in_array('LINKED_TO_HUB', $this->features);
    }

    protected function getFeatureMemberVerificationGateEnabledAttribute(): bool
    {
        return in_array('MEMBER_VERIFICATION_GATE_ENABLED', $this->features);
    }

    protected function getFeatureMonetizationEnabledAttribute(): bool
    {
        return in_array('MONETIZATION_ENABLED', $this->features);
    }

    protected function getFeatureMoreStickersAttribute(): bool
    {
        return in_array('MORE_STICKERS', $this->features);
    }

    protected function getFeatureNewsAttribute(): bool
    {
        return in_array('NEWS', $this->features);
    }

    protected function getFeaturePartneredAttribute(): bool
    {
        return in_array('PARTNERED', $this->features);
    }

    protected function getFeaturePreviewEnabledAttribute(): bool
    {
        return in_array('PREVIEW_ENABLED', $this->features);
    }

    protected function getFeaturePrivateThreadsAttribute(): bool
    {
        return in_array('PRIVATE_THREADS', $this->features);
    }

    protected function getFeatureRaidAlertsEnabledAttribute(): bool
    {
        return in_array('RAID_ALERTS_ENABLED', $this->features);
    }

    protected function getFeatureRoleIconsAttribute(): bool
    {
        return in_array('ROLE_ICONS', $this->features);
    }

    protected function getFeatureRoleSubscriptionsAvailableForPurchaseAttribute(): bool
    {
        return in_array('ROLE_SUBSCRIPTIONS_AVAILABLE_FOR_PURCHASE', $this->features);
    }

    protected function getFeatureRoleSubscriptionsEnabledAttribute(): bool
    {
        return in_array('ROLE_SUBSCRIPTIONS_ENABLED', $this->features);
    }

    protected function getFeatureTicketedEventsEnabledAttribute(): bool
    {
        return in_array('TICKETED_EVENTS_ENABLED', $this->features);
    }

    protected function getFeatureVanityUrlAttribute(): bool
    {
        return in_array('VANITY_URL', $this->features);
    }

    protected function getFeatureVerifiedAttribute(): bool
    {
        return in_array('VERIFIED', $this->features);
    }

    protected function getFeatureVipRegionsAttribute(): bool
    {
        return in_array('VIP_REGIONS', $this->features);
    }

    protected function getFeatureWelcomeScreenEnabledAttribute(): bool
    {
        return in_array('WELCOME_SCREEN_ENABLED', $this->features);
    }

    /**
     * Gets the voice regions available.
     *
     * @link https://discord.com/developers/docs/resources/voice#list-voice-regions
     *
     * @return ExtendedPromiseInterface<Collection>
     */
    public function getVoiceRegions(): ExtendedPromiseInterface
    {
        if (null !== $this->regions) {
            return resolve($this->regions);
        }

        return $this->http->get('voice/regions')->then(function ($regions) {
            $regions = new Collection($regions);

            $this->regions = $regions;

            return $regions;
        });
    }

    /**
     * Creates a role.
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-role
     *
     * @param array       $data   The data to fill the role with.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_roles permission.
     *
     * @return ExtendedPromiseInterface<Role>
     */
    public function createRole(array $data = [], ?string $reason = null): ExtendedPromiseInterface
    {
        $botperms = $this->getBotPermissions();

        if ($botperms && ! $botperms->manage_roles) {
            return reject(new NoPermissionsException("You do not have permission to manage roles in the guild {$this->id}."));
        }

        return $this->roles->save($this->factory->part(Role::class, $data), $reason);
    }

    /**
     * Creates an Emoji for the guild.
     *
     * @link https://discord.com/developers/docs/resources/emoji#create-guild-emoji
     *
     * @param array       $options          An array of options.
     * @param string      $options['name']  Name of the emoji.
     * @param string|null $options['image'] The 128x128 emoji image (if not using `$filepath`).
     * @param array|null  $options['roles'] Roles allowed to use this emoji.
     * @param string|null $filepath         The path to the file if specified will override image data string.
     * @param string|null $reason           Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_guild_expressions permission.
     * @throws FileNotFoundException  File does not exist.
     *
     * @return ExtendedPromiseInterface<Emoji>
     */
    public function createEmoji(array $options, ?string $filepath = null, ?string $reason = null): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'name',
                'image',
                'roles',
            ])
            ->setRequired('name')
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('image', 'string')
            ->setAllowedTypes('roles', 'array')
            ->setDefault('roles', []);

        if (null === $filepath) {
            $resolver->setRequired('image');
        }

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild_expressions) {
            return reject(new NoPermissionsException("You do not have permission to create emojis in the guild {$this->id}."));
        }

        if (isset($filepath)) {
            if (! file_exists($filepath)) {
                return reject(new FileNotFoundException("File does not exist at path {$filepath}."));
            }

            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            if ($extension == 'jpg') {
                $extension = 'jpeg';
            }
            $contents = file_get_contents($filepath);

            $options['image'] = "data:image/{$extension};base64,".base64_encode($contents);
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_EMOJIS, $this->id), $options, $headers)
            ->then(function ($response) {
                if (! $emojiPart = $this->emojis->get('id', $response->id)) {
                    $emojiPart = $this->emojis->create($response, true);
                    $this->emojis->pushItem($emojiPart);
                }

                return $emojiPart;
            });
    }

    /**
     * Creates an Sticker for the guild.
     *
     * @link https://discord.com/developers/docs/resources/sticker#create-guild-sticker
     *
     * @param array       $options                An array of options.
     * @param string      $options['name']        Name of the sticker.
     * @param string|null $options['description'] Description of the sticker (empty or 2-100 characters).
     * @param string      $options['tags']        Autocomplete/suggestion tags for the sticker (max 200 characters).
     * @param string      $filepath               The sticker file to upload, must be a PNG, APNG, or Lottie JSON file, max 512 KB.
     * @param string|null $reason                 Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_guild_expressions permission.
     * @throws FileNotFoundException  The file does not exist.
     * @throws \LengthException       Description is not 2-100 characters long.
     * @throws \DomainException       File format is not PNG, APNG, or Lottie JSON.
     * @throws \RuntimeException      Guild is not verified or partnered to upload Lottie stickers.
     *
     * @return ExtendedPromiseInterface<Sticker>
     */
    public function createSticker(array $options, string $filepath, ?string $reason = null): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver
            ->setDefined([
                'name',
                'description',
                'tags',
            ])
            ->setRequired(['name', 'tags'])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('description', 'string')
            ->setAllowedTypes('tags', 'string')
            ->setDefault('description', '');

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild_expressions) {
            return reject(new NoPermissionsException("You do not have permission to create stickers in the guild {$this->id}."));
        }

        if (! file_exists($filepath)) {
            return reject(new FileNotFoundException("File does not exist at path {$filepath}."));
        }

        $descLength = poly_strlen($options['description']);
        if ($descLength > 100 || $descLength == 1) {
            return reject(new \LengthException("Description must be 2 to 100 characters, given {$descLength}."));
        }

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

        if (function_exists('mime_content_type')) {
            $contentType = \mime_content_type($filepath);
        } else {
            $contentTypes = [
                'png' => 'image/png',
                'apng' => 'image/apng',
                //'gif' => 'image/gif', // Currently disabled in API
                'lottie' => 'application/json',
            ];

            if (! array_key_exists($extension, $contentTypes)) {
                return reject(new \DomainException("File format must be PNG, APNG, or Lottie JSON, given {$extension}."));
            }

            $contentType = $contentTypes[$extension];
        }

        if ($extension == 'lottie' && ! ($this->feature_verified || $this->feature_partnered)) {
            return reject(new \RuntimeException('Lottie stickers can be only uploaded in verified or partnered guilds.'));
        }

        $contents = file_get_contents($filepath);

        $multipart = new Multipart([
            [
                'name' => 'name',
                'content' => $options['name'],
            ],
            [
                'name' => 'description',
                'content' => $options['description'],
            ],
            [
                'name' => 'tags',
                'content' => $options['tags'],
            ],
            [
                'name' => 'file',
                'filename' => basename($filepath),
                'content' => $contents,
                'headers' => [
                    'Content-Type' => $contentType,
                ],
            ],
        ]);

        $headers = $multipart->getHeaders();
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_STICKERS, $this->id), (string) $multipart, $headers)
            ->then(function ($response) {
                if (! $stickerPart = $this->stickers->get('id', $response->id)) {
                    $stickerPart = $this->stickers->create($response, true);
                    $this->stickers->pushItem($stickerPart);
                }

                return $stickerPart;
            });
    }

    /**
     * Leaves the guild.
     *
     * @return ExtendedPromiseInterface
     */
    public function leave(): ExtendedPromiseInterface
    {
        return $this->discord->guilds->leave($this->id);
    }

    /**
     * Transfers ownership of the guild to another member.
     *
     * @param Member|int  $member The member to transfer ownership to.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException Ownership not transferred correctly.
     *
     * @return ExtendedPromiseInterface
     */
    public function transferOwnership($member, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($member instanceof Member) {
            $member = $member->id;
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD), ['owner_id' => $member], $headers)->then(function ($response) use ($member) {
            if ($response->owner_id != $member) {
                throw new \RuntimeException('Ownership was not transferred correctly.');
            }

            return $this;
        });
    }

    /**
     * Validates the specified region.
     *
     * @deprecated 10.0.0 Use `Channel::$rtc_region`.
     *
     * @return ExtendedPromiseInterface
     *
     * @see Guild::REGION_DEFAULT The default region.
     */
    public function validateRegion(): ExtendedPromiseInterface
    {
        return $this->getVoiceRegions()->then(function () {
            $regions = $this->regions->map(function ($region) {
                return $region->id;
            })->toArray();

            if (! in_array($this->region, $regions)) {
                return self::REGION_DEFAULT;
            }

            return 'deprecated';
        });
    }

    /**
     * Returns an audit log object for the query.
     *
     * @link https://discord.com/developers/docs/resources/audit-log#get-guild-audit-log
     *
     * @param array                   $options                An array of options.
     * @param string|Member|User|null $options['user_id']     filter the log for actions made by a user
     * @param int|null                $options['action_type'] the type of audit log event
     * @param string|Entry|null       $options['before']      filter the log before a certain entry id (sort by descending)
     * @param string|Entry|null       $options['affter']      filter the log after a certain entry id (sort by ascending)
     * @param int|null                $options['limit']       how many entries are returned (default 50, minimum 1, maximum 100)
     *
     * @throws NoPermissionsException Missing view_audit_log permission.
     *
     * @return ExtendedPromiseInterface<AuditLog>
     */
    public function getAuditLog(array $options = []): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'user_id',
            'action_type',
            'before',
            'after',
            'limit',
        ])
        ->setAllowedTypes('user_id', ['string', 'int', Member::class, User::class])
        ->setAllowedTypes('action_type', 'int')
        ->setAllowedTypes('before', ['string', 'int', Entry::class])
        ->setAllowedTypes('after', ['string', 'int', Entry::class])
        ->setAllowedTypes('limit', 'int')
        ->setAllowedValues('action_type', array_values((new ReflectionClass(Entry::class))->getConstants()))
        ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 100))
        ->setNormalizer('user_id', normalizePartId())
        ->setNormalizer('before', normalizePartId())
        ->setNormalizer('after', normalizePartId());

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->view_audit_log) {
            return reject(new NoPermissionsException("You do not have permission to view audit log in the guild {$this->id}."));
        }

        $endpoint = Endpoint::bind(Endpoint::AUDIT_LOG, $this->id);

        foreach ($options as $key => $value) {
            $endpoint->addQuery($key, $value);
        }

        return $this->http->get($endpoint)->then(function ($response) {
            return $this->factory->part(AuditLog::class, (array) $response + ['guild_id' => $this->id], true);
        });
    }

    /**
     * Returns the bot's permissions in the guild.
     *
     * @return RolePermission|null
     */
    public function getBotPermissions(): ?RolePermission
    {
        if (! $memberPart = $this->members->get('id', $this->discord->id)) {
            return null;
        }

        return $memberPart->getPermissions();
    }

    /**
     * Updates the positions of a list of given roles.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-role-positions
     *
     * @param array $roles Associative array where the LHS key is the position,
     *                     and the RHS value is a `Role` object or a string ID,
     *                     e.g. `[1 => 'role_id_1', 3 => 'role_id_3']`.
     *
     * @throws NoPermissionsException Missing manage_roles permission.
     *
     * @return ExtendedPromiseInterface<self>
     */
    public function updateRolePositions(array $roles): ExtendedPromiseInterface
    {
        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_roles) {
            return reject(new NoPermissionsException("You do not have permission to update role positions in the guild {$this->id}."));
        }

        $payload = [];

        foreach ($roles as $position => $role) {
            $payload[] = [
                'id' => ($role instanceof Role) ? $role->id : $role,
                'position' => $position,
            ];
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_ROLES, $this->id), $payload)
            ->then(function ($response) {
                foreach ($response as $role) {
                    if ($rolePart = $this->roles->get('id', $role->id)) {
                        $rolePart->fill((array) $role);
                    } else {
                        $rolePart = $this->roles->create($role, true);
                        $this->roles->pushItem($rolePart);
                    }
                }

                return $this;
            });
    }

    /**
     * Returns a list of guild member objects whose username or nickname starts
     * with a provided string.
     *
     * @link https://discord.com/developers/docs/resources/guild#search-guild-members
     *
     * @param array       $options          An array of options. All fields are optional.
     * @param string|null $options['query'] Query string to match username(s) and nickname(s) against
     * @param int|null    $options['limit'] How many entries are returned (default 1, minimum 1, maximum 1000)
     *
     * @return ExtendedPromiseInterface<Collection|Member[]>
     */
    public function searchMembers(array $options): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'query',
            'limit',
        ])
        ->setDefault('limit', 1)
        ->setAllowedTypes('query', 'string')
        ->setAllowedTypes('limit', 'int')
        ->setAllowedValues('limit', fn ($value) => ($value >= 1 && $value <= 1000));

        $options = $resolver->resolve($options);

        $endpoint = Endpoint::bind(Endpoint::GUILD_MEMBERS_SEARCH, $this->id);
        $endpoint->addQuery('query', $options['query']);
        $endpoint->addQuery('limit', $options['limit']);

        return $this->http->get($endpoint)->then(function ($responses) {
            $members = Collection::for(Member::class);

            foreach ($responses as $response) {
                if (! $member = $this->members->get('id', $response->user->id)) {
                    $member = $this->members->create($response, true);
                    $this->members->pushItem($member);
                }

                $members->pushItem($member);
            }

            return $members;
        });
    }

    /**
     * Returns the number of members that would be removed in a prune operation.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-prune-count
     *
     * @param array                $options                  An array of options.
     * @param int|null             $options['days']          Number of days to count prune for (1-30), defaults to 7.
     * @param string[]|Role[]|null $options['include_roles'] Roles to include, defaults to none.
     *
     * @throws NoPermissionsException Missing kick_members permission.
     *
     * @return ExtendedPromiseInterface<int> The number of members that would be removed.
     */
    public function getPruneCount(array $options = []): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'days',
            'include_roles',
        ])
        ->setDefault('days', 7)
        ->setAllowedTypes('days', 'int')
        ->setAllowedTypes('include_roles', 'array')
        ->setAllowedValues('days', fn ($value) => ($value >= 1 && $value <= 30))
        ->setNormalizer('include_roles', function ($option, $values) {
            foreach ($values as &$value) {
                if ($value instanceof Role) {
                    $value = $value->id;
                }
            }

            return $values;
        });

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->kick_members) {
            return reject(new NoPermissionsException("You do not have permission to get prune count in the guild {$this->id}."));
        }

        $endpoint = Endpoint::bind(Endpoint::GUILD_PRUNE, $this->id);
        $endpoint->addQuery('days', $options['days']);
        if (isset($options['include_roles'])) {
            $endpoint->addQuery('include_roles', implode(',', $options['include_roles']));
        }

        return $this->http->get($endpoint)->then(function ($response) {
            return $response->pruned;
        });
    }

    /**
     * Begin a prune members operation.
     * For large guilds it's recommended to set the `compute_prune_count` option
     * to `false`, forcing 'pruned' to null.
     *
     * @link https://discord.com/developers/docs/resources/guild#begin-guild-prune
     *
     * @param array                $options                        An array of options.
     * @param int|null             $options['days']                Number of days to count prune for (1-30), defaults to 7.
     * @param int|null             $options['compute_prune_count'] Whether 'pruned' is returned, discouraged for large guilds.
     * @param string[]|Role[]|null $options['include_roles']       Roles to include, defaults to none.
     * @param string               $reason                         Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing kick_members permission.
     *
     * @return ExtendedPromiseInterface<?int> The number of members that were removed in the prune operation.
     */
    public function beginPrune(array $options = [], ?string $reason = null): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'days',
            'compute_prune_count',
            'include_roles',
        ])
        ->setDefaults(['days' => 7, 'compute_prune_count' => true])
        ->setAllowedTypes('days', 'int')
        ->setAllowedTypes('compute_prune_count', 'bool')
        ->setAllowedTypes('include_roles', 'array')
        ->setAllowedValues('days', fn ($value) => ($value >= 1 && $value <= 30))
        ->setNormalizer('include_roles', function ($option, $values) {
            foreach ($values as &$value) {
                if ($value instanceof Role) {
                    $value = $value->id;
                }
            }

            return $values;
        });

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->kick_members) {
            return reject(new NoPermissionsException("You do not have permission to prune members in the guild {$this->id}."));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_PRUNE, $this->id), $options, $headers)->then(function ($response) {
            return $response->pruned;
        });
    }

    /**
     * Get the Welcome Screen for the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-welcome-screen
     *
     * @param bool $fresh Whether we should skip checking the cache.
     *
     * @throws NoPermissionsException Missing manage_guild permission when the welcome screen is not enabled.
     *
     * @return ExtendedPromiseInterface<WelcomeScreen>
     */
    public function getWelcomeScreen(bool $fresh = false): ExtendedPromiseInterface
    {
        if (! $this->feature_welcome_screen_enabled) {
            $botperms = $this->getBotPermissions();
            if ($botperms && ! $botperms->manage_guild) {
                return reject(new NoPermissionsException("You do not have permission to get welcome screen of the guild {$this->id}."));
            }
        }

        if (! $fresh && $welcomeScreen = $this->welcome_screen) {
            return resolve($welcomeScreen);
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_WELCOME_SCREEN, $this->id))->then(function ($response) {
            $this->attributes['welcome_screen'] = $response;

            return $this->factory->part(WelcomeScreen::class, (array) $response, true);
        });
    }

    /**
     * Returns the Welcome Screen object for the guild.
     *
     * @return WelcomeScreen|null
     */
    protected function getWelcomeScreenAttribute(): ?WelcomeScreen
    {
        if (! isset($this->attributes['welcome_screen'])) {
            return null;
        }

        return $this->createOf(WelcomeScreen::class, $this->attributes['welcome_screen']);
    }

    /**
     * Modify the guild's Welcome Screen. Requires the MANAGE_GUILD permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-welcome-screen
     *
     * @param array                          $options                     An array of options. All fields are optional.
     * @param bool|null                      $options['enabled']          Whether the welcome screen is enabled.
     * @param object[]|WelcomeChannel[]|null $options['welcome_channels'] Channels linked in the welcome screen and their display options (maximum 5).
     * @param string|null                    $options['description']      The server description to show in the welcome screen (maximum 140).
     *
     * @throws NoPermissionsException Missing manage_guild permission.
     *
     * @return ExtendedPromiseInterface<WelcomeScreen> The updated Welcome Screen.
     */
    public function updateWelcomeScreen(array $options): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'enabled',
            'welcome_channels',
            'description',
        ])
        ->setAllowedTypes('enabled', 'bool')
        ->setAllowedTypes('welcome_channels', 'array')
        ->setAllowedTypes('description', 'string')
        ->setNormalizer('welcome_channels', function ($option, $values) {
            foreach ($values as &$value) {
                if ($value instanceof WelcomeChannel) {
                    $value = $value->getRawAttributes();
                }
            }

            return $values;
        });

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild) {
            return reject(new NoPermissionsException("You do not have permission to update welcome screen of the guild {$this->id}."));
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_WELCOME_SCREEN, $this->id), $options)->then(function ($response) {
            $this->attributes['welcome_screen'] = $response;

            return $this->factory->part(WelcomeScreen::class, (array) $response, true);
        });
    }

    /**
     * Fetch the Widget Settings for the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-widget-settings
     *
     * @throws NoPermissionsException Missing manage_guild permission.
     *
     * @return ExtendedPromiseInterface
     */
    public function getWidgetSettings(): ExtendedPromiseInterface
    {
        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild) {
            return reject(new NoPermissionsException("You do not have permission to get widget settings of the guild {$this->id}."));
        }

        return $this->http->get(Endpoint::bind(Endpoint::GUILD_WIDGET_SETTINGS, $this->id))->then(function ($response) {
            $this->widget_enabled = $response->enabled;
            $this->widget_channel_id = $response->channel_id;

            return $response;
        });
    }

    /**
     * Modify a guild widget settings object for the guild. All attributes may
     * be passed in with JSON and modified. Requires the MANAGE_GUILD permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-widget
     *
     * @param array  $options An array of options.
     *                        enabled => whether the widget is enabled
     *                        channel_id => the widget channel id
     * @param string $reason  Reason for Audit Log.
     *
     * @throws NoPermissionsException Missing manage_guild permission.
     *
     * @return ExtendedPromiseInterface The updated guild widget object.
     */
    public function updateWidgetSettings(array $options, ?string $reason = null): ExtendedPromiseInterface
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined([
            'enabled',
            'channel_id',
        ])
        ->setAllowedTypes('enabled', 'bool')
        ->setAllowedTypes('channel_id', ['string', 'null']);

        $options = $resolver->resolve($options);

        $botperms = $this->getBotPermissions();
        if ($botperms && ! $botperms->manage_guild) {
            return reject(new NoPermissionsException("You do not have permission to update widget settings of the guild {$this->id}."));
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD_WIDGET_SETTINGS, $this->id), $options, $headers)->then(function ($response) {
            $this->widget_enabled = $response->enabled;
            $this->widget_channel_id = $response->channel_id;

            return $response;
        });
    }

    /**
     * Get the Widget for the guild.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-widget
     *
     * @return ExtendedPromiseInterface<Widget>
     */
    public function getWidget(): ExtendedPromiseInterface
    {
        return (new Widget($this->discord, ['id' => $this->id]))->fetch();
    }

    /**
     * Attempts to create an Invite to a channel in this guild where possible.
     *
     * @see Channel::createInvite()
     *
     * @throws \RuntimeException      No possible channels to create Invite on.
     * @throws NoPermissionsException
     *
     * @return ExtendedPromiseInterface<Invite>
     */
    public function createInvite(...$args): ExtendedPromiseInterface
    {
        $channel = $this->channels->find(function (Channel $channel) {
            if ($channel->canInvite()) {
                if ($botperms = $channel->getBotPermissions()) {
                    return $botperms->create_instant_invite;
                }

                return true;
            }

            return false;
        });

        if (! $channel) {
            return reject(new \RuntimeException("No channels found to create an Invite to the guild {$this->id}."));
        }

        return $channel->createInvite($args);
    }

    /**
     * Modify the Guild `mfa_level`, requires guild ownership.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-mfa-level
     *
     * @param int         $level  The new MFA level `Guild::MFA_NONE` or `Guild::MFA_ELEVATED`.
     * @param string|null $reason Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<self> This guild.
     */
    public function updateMFALevel(int $level, ?string $reason = null): ExtendedPromiseInterface
    {
        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->post(Endpoint::bind(Endpoint::GUILD_MFA, $this->id), ['level' => $level], $headers)->then(function ($response) {
            $this->mfa_level = $response->level;

            return $this;
        });
    }

    /**
     * Modify the guild feature.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild
     * @link https://discord.com/developers/docs/resources/guild#guild-object-mutable-guild-features
     *
     * @param bool[]      $features Array of features to set/unset, e.g. `['COMMUNITY' => true, 'INVITES_DISABLED' => false]`.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @throws \OutOfRangeException   Feature is not mutable.
     * @throws \RuntimeException      Guild feature is already set.
     * @throws NoPermissionsException Missing various permissions:
     *                                administrator for COMMUNITY or DISCOVERABLE.
     *                                manage_guild for INVITES_DISABLED or RAID_ALERTS_ENABLED.
     *
     * @return ExtendedPromiseInterface<self> This guild.
     */
    public function setFeatures(array $features, ?string $reason = null): ExtendedPromiseInterface
    {
        if ($botperms = $this->getBotPermissions()) {
            if ((isset($features['COMMUNITY']) || isset($features['DISCOVERABLE'])) && ! $botperms->administrator) {
                return reject(new NoPermissionsException('You do not have administrator permission to modify the guild feature COMMUNITY or DISCOVERABLE.'));
            }
            if ((isset($features['INVITES_DISABLED']) || isset($features['RAID_ALERTS_ENABLED'])) && ! $botperms->manage_guild) {
                return reject(new NoPermissionsException('You do not have manage guild permission to modify the guild feature INVITES_DISABLED or RAID_ALERTS_ENABLED.'));
            }
        }

        $setFeatures = $this->features;
        foreach ($features as $feature => $set) {
            if (! in_array($feature, ['COMMUNITY', 'INVITES_DISABLED', 'DISCOVERABLE', 'RAID_ALERTS_ENABLED'])) {
                return reject(new \OutOfRangeException("Guild feature {$feature} is not mutable"));
            }
            $featureIdx = array_search($feature, $setFeatures);
            if ($set) {
                if ($featureIdx !== false) {
                    return reject(new \RuntimeException("Guild feature {$feature} is already set"));
                }
                $setFeatures[] = $feature;
            } else {
                if ($featureIdx === false) {
                    return reject(new \RuntimeException("Guild feature {$feature} is already not set"));
                }
                unset($setFeatures[$featureIdx]);
            }
        }

        $headers = [];
        if (isset($reason)) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }

        return $this->http->patch(Endpoint::bind(Endpoint::GUILD, $this->id), ['features' => array_values($setFeatures)], $headers)->then(function ($response) {
            $this->features = $response->features;

            return $this;
        });
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-json-params
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
        ] + $this->makeOptionalAttributes([
            'icon' => $this->attributes['icon'] ?? null,
            'verification_level' => $this->verification_level,
            'default_message_notifications' => $this->default_message_notifications,
            'explicit_content_filter' => $this->explicit_content_filter,
            'roles' => array_values(array_map(function (Role $role) {
                return $role->getCreatableAttributes();
            }, $this->roles->toArray())),
            'channels' => array_values(array_map(function (Channel $channel) {
                return $channel->getCreatableAttributes();
            }, $this->channels->toArray())),
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
            'system_channel_id' => $this->system_channel_id,
            'system_channel_flags' => $this->system_channel_flags,
        ]);
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'name' => $this->name,
            'verification_level' => $this->verification_level,
            'default_message_notifications' => $this->default_message_notifications,
            'explicit_content_filter' => $this->explicit_content_filter,
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
            'icon' => $this->attributes['icon'] ?? null,
            'splash' => $this->splash_hash,
            'discovery_splash' => $this->attributes['discovery_splash'] ?? null,
            'banner' => $this->attributes['banner'] ?? null,
            'system_channel_id' => $this->system_channel_id,
            'system_channel_flags' => $this->system_channel_flags,
            'rules_channel_id' => $this->rules_channel_id,
            'public_updates_channel_id' => $this->public_updates_channel_id,
            'preferred_locale' => $this->preferred_locale,
            'description' => $this->description,
            'premium_progress_bar_enabled' => $this->premium_progress_bar_enabled,
            'safety_alerts_channel_id' => $this->safety_alerts_channel_id,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'guild_id' => $this->id,
        ];
    }

    /**
     * Returns the timestamp of when the guild was created.
     *
     * @return float
     */
    public function createdTimestamp()
    {
        return \Discord\getSnowflakeTimestamp($this->id);
    }
}
