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

use function Discord\poly_strlen;
use function React\Promise\reject;
use function React\Promise\resolve;

/**
 * A Guild is Discord's equivalent of a server. It contains all the Members, Channels, Roles, Bans etc.
 *
 * @link https://discord.com/developers/docs/resources/guild
 *
 * @since 2.0.0 Refactored as Part
 * @since 1.0.0
 *
 * @property      string             $id                                       The unique identifier of the guild.
 * @property      string             $name                                     The name of the guild.
 * @property      ?string            $icon                                     The URL to the guild icon.
 * @property      ?string|null       $icon_hash                                The icon hash for the guild.
 * @property      ?string            $splash                                   The URL to the guild splash.
 * @property      ?string|null       $splash_hash                              The splash hash for the guild.
 * @property      ?string            $discovery_splash                         Discovery splash hash. Only for discoverable guilds.
 * @property      string             $owner_id                                 The unique identifier of the owner of the guild.
 * @property-read User|null          $owner                                    The owner of the guild.
 * @property      ?string|null       $region                                   The region the guild's voice channels are hosted in.
 * @property      string             $afk_channel_id                           The unique identifier of the AFK channel ID.
 * @property      int                $afk_timeout                              How long in seconds you will remain in the voice channel until you are moved into the AFK channel. Can be set to: 60, 300, 900, 1800, 3600.
 * @property      bool|null          $widget_enabled                           Is server widget enabled.
 * @property      ?string|null       $widget_channel_id                        Channel that the widget will create an invite to.
 * @property      int                $verification_level                       The verification level used for the guild.
 * @property      int                $default_message_notifications            Default notification level.
 * @property      int                $explicit_content_filter                  Explicit content filter level.
 * @property      RoleRepository     $roles                                    Roles in the guild.
 * @property      EmojiRepository    $emojis                                   Custom guild emojis.
 * @property      string[]           $features                                 An array of features that the guild has.
 * @property-read bool               $feature_animated_banner                  Guild has access to set an animated guild banner image.
 * @property-read bool               $feature_animated_icon                    Guild has access to set an animated guild icon.
 * @property-read bool               $feature_auto_moderation                  Guild has set up auto moderation rules.
 * @property-read bool               $feature_banner                           Guild has access to set a guild banner image.
 * @property-read bool               $feature_community                        Guild can enable welcome screen, Membership Screening, stage channels and discovery, and receives community updates.
 * @property-read bool               $feature_developer_support_server         Guild has been set as a support server on the App Directory.
 * @property-read bool               $feature_discoverable                     Guild is able to be discovered in the directory.
 * @property-read bool               $feature_featurable                       Guild is able to be featured in the directory.
 * @property-read bool               $feature_has_directory_entry              Guild is listed in a directory channel.
 * @property-read bool               $feature_invites_disabled                 Guild has paused invites, preventing new users from joining.
 * @property-read bool               $feature_invite_splash                    Guild has access to set an invite splash background.
 * @property-read bool               $feature_linked_to_hub                    Guild is in a Student Hub.
 * @property-read bool               $feature_member_verification_gate_enabled Guild has enabled membership screening.
 * @property-read bool               $feature_monetization_enabled             Guild has enabled monetization.
 * @property-read bool               $feature_more_stickers                    Guild has increased custom sticker slots.
 * @property-read bool               $feature_news                             Guild has access to create news channels.
 * @property-read bool               $feature_partnered                        Guild is partnered.
 * @property-read bool               $feature_preview_enabled                  Guild can be previewed before joining via membership screening or the directory.
 * @property-read bool               $feature_private_threads                  Guild has access to create private threads.
 * @property-read bool               $feature_role_icons                       Guild is able to set role icons.
 * @property-read bool               $feature_ticketed_events_enabled          Guild has enabled ticketed events.
 * @property-read bool               $feature_vanity_url                       Guild has access to set a vanity url.
 * @property-read bool               $feature_verified                         Guild is verified.
 * @property-read bool               $feature_vip_regions                      Guild has access to set 384kbps bitrate in voice.
 * @property-read bool               $feature_welcome_screen_enabled           Guild has enabled the welcome screen.
 * @property      int                $mfa_level                                MFA level required to join.
 * @property      ?string            $application_id                           Application that made the guild, if made by one.
 * @property      ?string            $system_channel_id                        Channel that system notifications are posted in.
 * @property      int                $system_channel_flags                     Flags for the system channel.
 * @property      ?string            $rules_channel_id                         Channel that the rules are in.
 * @property      int|null           $max_presences                            Maximum amount of presences allowed in the guild.
 * @property      int|null           $max_members                              Maximum amount of members allowed in the guild.
 * @property      ?string            $vanity_url_code                          Vanity URL code for the guild.
 * @property      ?string            $description                              Guild description of a guild.
 * @property      ?string            $banner                                   Banner hash.
 * @property      int                $premium_tier                             Server boost level.
 * @property      int|null           $premium_subscription_count               Number of boosts in the guild.
 * @property      string             $preferred_locale                         Preferred locale of the guild.
 * @property      ?string            $public_updates_channel_id                Notice channel id.
 * @property      int|null           $max_video_channel_users                  Maximum amount of users allowed in a video channel.
 * @property      int|null           $max_stage_video_channel_users            Maximum amount of users in a stage video channel.
 * @property      int|null           $approximate_member_count                 Approximate number of members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property      int|null           $approximate_presence_count               Approximate number of non-offline members in this guild, returned from the GET /guilds/<id> endpoint when with_counts is true.
 * @property-read WelcomeScreen|null $welcome_screen                           The welcome screen of a Community guild, shown to new members, returned in an Invite's guild object. use `getWelcomeScreen` first to populate.
 * @property      int                $nsfw_level                               The guild NSFW level.
 * @property      StickerRepository  $stickers                                 Custom guild stickers.
 * @property      bool               $premium_progress_bar_enabled             Whether the guild has the boost progress bar enabled.
 *
 * @property Carbon|null              $joined_at              A timestamp of when the current user joined the guild.
 * @property bool|null                $large                  Whether the guild is considered 'large' (over 250 members).
 * @property int|null                 $member_count           How many members are in the guild.
 * @property object[]|null            $voice_states           Array of voice states.
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

    public const HUB_TYPE_DEFAULT = 0;
    public const HUB_TYPE_HIGH_SCHOOL = 1;
    public const HUB_TYPE_COLLEGE = 2;

    /**
     * @inheritDoc
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
        'emojis',
        'banner',
        'owner_id',
        'application_id',
        'region',
        'afk_channel_id',
        'afk_timeout',
        'system_channel_id',
        'widget_enabled',
        'widget_channel_id',
        'verification_level',
        'roles',
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
        'stickers',
        'premium_progress_bar_enabled',

        // events
        'joined_at',
        'large',
        'member_count',
    ];

    /**
     * @inheritDoc
     */
    protected $visible = [
        'feature_animated_banner',
        'feature_animated_icon',
        'feature_auto_moderation',
        'feature_banner',
        'feature_community',
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
        'feature_role_icons',
        'feature_ticketed_events_enabled',
        'feature_vanity_url',
        'feature_verified',
        'feature_vip_regions',
        'feature_welcome_screen_enabled',
    ];

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function fill(array $attributes): void
    {
        parent::fill($attributes);

        foreach ($attributes['roles'] ?? [] as $role) {
            $role = (array) $role + ['guild_id' => $this->id];
            if ($rolePart = $this->roles->offsetGet($role['id'])) {
                $rolePart->fill($role);
            }
            $this->roles->pushItem($rolePart ?? $this->factory->part(Role::class, $role, $this->created));
        }

        foreach ($attributes['emojis'] ?? [] as $emoji) {
            $emoji = (array) $emoji + ['guild_id' => $this->id];
            if ($emojiPart = $this->emojis->offsetGet($emoji['id'])) {
                $emojiPart->fill($emoji);
            }
            $this->emojis->pushItem($emojiPart ?? $this->factory->part(Emoji::class, $emoji, $this->created));
        }

        foreach ($attributes['stickers'] ?? [] as $sticker) {
            $sticker = (array) $sticker + ['guild_id' => $this->id];
            if ($stickerPart = $this->stickers->offsetGet($sticker['id'])) {
                $stickerPart->fill($sticker);
            }
            $this->stickers->pushItem($stickerPart ?? $this->factory->part(Sticker::class, $sticker, $this->created));
        }

        foreach ($attributes['channels'] ?? [] as $channel) {
            $channel = (array) $channel + ['guild_id' => $this->id];
            if ($channelPart = $this->channels->offsetGet($channel['id'])) {
                $channelPart->fill($channel);
            }
            $this->channels->pushItem($channelPart ?? $this->factory->part(Channel::class, $channel, true));
        }
    }

    /**
     * Returns the channels invites.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-invites
     *
     * @return ExtendedPromiseInterface<Collection|Invite[]>
     */
    public function getInvites(): ExtendedPromiseInterface
    {
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
     * @return ExtendedPromiseInterface
     */
    public function unban($user): ExtendedPromiseInterface
    {
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
     * @return Collection[]|StageInstance[]|null
     */
    protected function getStageInstancesAttribute(): ?Collection
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

    protected function getFeatureTicketedEventsEnabledAttribute(): bool
    {
        return in_array('TICKETED_EVENTS_ENABLED', $this->features);
    }

    protected function getFeatureMonetizationEnabledAttribute(): bool
    {
        return in_array('MONETIZATION_ENABLED', $this->features);
    }

    protected function getFeatureMoreStickersAttribute(): bool
    {
        return in_array('MORE_STICKERS', $this->features);
    }

    protected function getFeaturePrivateThreadsAttribute(): bool
    {
        return in_array('PRIVATE_THREADS', $this->features);
    }

    protected function getFeatureRoleIconsAttribute(): bool
    {
        return in_array('ROLE_ICONS', $this->features);
    }

    /**
     * Gets the voice regions available.
     *
     * @link https://discord.com/developers/docs/resources/voice#list-voice-regions
     *
     * @return ExtendedPromiseInterface
     */
    public function getVoiceRegions(): ExtendedPromiseInterface
    {
        if (! is_null($this->regions)) {
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
     * @throws NoPermissionsException
     *
     * @return ExtendedPromiseInterface<Role>
     */
    public function createRole(array $data = [], ?string $reason = null): ExtendedPromiseInterface
    {
        $botperms = $this->members->get('id', $this->discord->id)->getPermissions();

        if ($botperms && ! $botperms->manage_roles) {
            return reject(new NoPermissionsException('You do not have permission to manage roles in the specified guild.'));
        }

        return $this->roles->save($this->factory->part(Role::class, $data), $reason);
    }

    /**
     * Creates an Emoji for the guild.
     *
     * @link https://discord.com/developers/docs/resources/emoji#create-guild-emoji
     *
     * @param array       $options  An array of options.
     *                              name => name of the emoji
     *                              image => the 128x128 emoji image
     *                              roles => roles allowed to use this emoji
     * @param string|null $filepath The path to the file if specified will override image data string.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @throws FileNotFoundException Thrown when the file does not exist.
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

        $options = $resolver->resolve($options);

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
                    $emojiPart = $this->factory->part(Emoji::class, (array) $response, true);
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
     * @param array       $options  An array of options.
     *                              name => Name of the sticker.
     *                              description => Description of the sticker (empty or 2-100 characters).
     *                              tags => Autocomplete/suggestion tags for the sticker (max 200 characters).
     * @param string      $filepath The sticker file to upload, must be a PNG, APNG, or Lottie JSON file, max 500 KB.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @throws FileNotFoundException Thrown when the file does not exist.
     * @throws \LengthException
     * @throws \DomainException
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

        if (! file_exists($filepath)) {
            return reject(new FileNotFoundException("File does not exist at path {$filepath}."));
        }

        $descLength = poly_strlen($options['description']);
        if ($descLength > 100 || $descLength == 1) {
            return reject(new \LengthException('Description must be 2 to 100 characters'));
        }

        if (function_exists('mime_content_type')) {
            $contentType = \mime_content_type($filepath);
        } else {
            $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $contentTypes = [
                'png' => 'image/png',
                'apng' => 'image/apng',
                'lottie' => 'application/json',
            ];

            if (! array_key_exists($extension, $contentTypes)) {
                return reject(new \DomainException('File format must be PNG, APNG, or Lottie JSON'));
            }

            $contentType = $contentTypes[$extension];
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
                    $stickerPart = $this->factory->part(Sticker::class, (array) $response, true);
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
     * Transfers ownership of the guild to
     * another member.
     *
     * @param Member|int  $member The member to transfer ownership to.
     * @param string|null $reason Reason for Audit Log.
     *
     * @throws \RuntimeException
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

            return $this->region;
        });
    }

    /**
     * Returns an audit log object for the query.
     *
     * @link https://discord.com/developers/docs/resources/audit-log#get-guild-audit-log
     *
     * @param array $options An array of options.
     *                       user_id => filter the log for actions made by a user
     *                       action_type => the type of audit log event
     *                       before => filter the log before a certain entry id
     *                       limit => how many entries are returned (default 50, minimum 1, maximum 100)
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
            'limit',
        ])
        ->setAllowedTypes('user_id', ['string', 'int', Member::class, User::class])
        ->setAllowedTypes('action_type', 'int')
        ->setAllowedTypes('before', ['string', 'int', Entry::class])
        ->setAllowedTypes('limit', 'int')
        ->setAllowedValues('action_type', array_values((new ReflectionClass(Entry::class))->getConstants()))
        ->setAllowedValues('limit', range(1, 100));

        $options = $resolver->resolve($options);

        if ($options['user_id'] ?? null instanceof Part) {
            $options['user_id'] = $options['user_id']->id;
        }

        if ($options['before'] ?? null instanceof Part) {
            $options['before'] = $options['before']->id;
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
     * Updates the positions of a list of given roles.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-role-positions
     *
     * The `$roles` array should be an associative array where the LHS key is the position,
     * and the RHS value is a `Role` object or a string ID, e.g. [1 => 'role_id_1', 3 => 'role_id_3'].
     *
     * @param array $roles
     *
     * @return ExtendedPromiseInterface
     */
    public function updateRolePositions(array $roles): ExtendedPromiseInterface
    {
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
                        $rolePart = $this->factory->part(Role::class, (array) $role + ['guild_id' => $this->id], true);
                        $this->roles->pushItem($rolePart);
                    }
                }

                return $this;
            });
    }

    /**
     * Returns a list of guild member objects whose username or nickname starts with a provided string.
     *
     * @link https://discord.com/developers/docs/resources/guild#search-guild-members
     *
     * @param array $options An array of options.
     *                       query => query string to match username(s) and nickname(s) against
     *                       limit => how many entries are returned (default 1, minimum 1, maximum 1000)
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
        ->setAllowedValues('limit', range(1, 1000));

        $options = $resolver->resolve($options);

        $endpoint = Endpoint::bind(Endpoint::GUILD_MEMBERS_SEARCH, $this->id);
        $endpoint->addQuery('query', $options['query']);
        $endpoint->addQuery('limit', $options['limit']);

        return $this->http->get($endpoint)->then(function ($responses) {
            $members = Collection::for(Member::class);

            foreach ($responses as $response) {
                if (! $member = $this->members->get('id', $response->user->id)) {
                    $member = $this->factory->part(Member::class, (array) $response + ['guild_id' => $this->id], true);
                    $this->members->pushItem($member);
                }

                $members->pushItem($member);
            }

            return $members;
        });
    }

    /**
     * Returns the number of members that would be removed in a prune operation.
     * Requires the KICK_MEMBERS permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-prune-count
     *
     * @param array $options An array of options.
     *                       days => number of days to count prune for (1-30)
     *                       include_roles => role id(s) to include
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
        ->setAllowedValues('days', range(1, 30));

        $options = $resolver->resolve($options);

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
     * For large guilds it's recommended to set the compute_prune_count option to false, forcing 'pruned' to null.
     * Requires the KICK_MEMBERS permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#get-guild-prune-count
     *
     * @param array  $options An array of options.
     *                        days => number of days to count prune for (1-30)
     *                        compute_prune_count => whether 'pruned' is returned, discouraged for large guilds
     *                        include_roles => role id(s) to include
     * @param string $reason  Reason for Audit Log.
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
        ->setAllowedValues('days', range(1, 30));

        $options = $resolver->resolve($options);

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
     * @return ExtendedPromiseInterface<WelcomeScreen>
     */
    public function getWelcomeScreen(bool $fresh = false): ExtendedPromiseInterface
    {
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

        return $this->factory->part(WelcomeScreen::class, (array) $this->attributes['welcome_screen'], true);
    }

    /**
     * Modify the guild's Welcome Screen. Requires the MANAGE_GUILD permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-welcome-screen
     *
     * @param array $options An array of options.
     *                       enabled => whether the welcome screen is enabled
     *                       welcome_channels => channels linked in the welcome screen and their display options (maximum 5)
     *                       description => the server description to show in the welcome screen (maximum 140)
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
        ->setAllowedTypes('description', 'string');

        $options = $resolver->resolve($options);

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
     * @return ExtendedPromiseInterface
     */
    public function getWidgetSettings(): ExtendedPromiseInterface
    {
        return $this->http->get(Endpoint::bind(Endpoint::GUILD_WIDGET_SETTINGS, $this->id))->then(function ($response) {
            $this->widget_enabled = $response->enabled;
            $this->widget_channel_id = $response->channel_id;

            return $response;
        });
    }

    /**
     * Modify a guild widget settings object for the guild. All attributes may be passed in with JSON and modified. Requires the MANAGE_GUILD permission.
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-widget
     *
     * @param array  $options An array of options.
     *                        enabled => whether the widget is enabled
     *                        channel_id => the widget channel id
     * @param string $reason  Reason for Audit Log.
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
     * @return ExtendedPromiseInterface<WelcomeScreen>
     */
    public function getWidget(): ExtendedPromiseInterface
    {
        return $this->factory->part(Widget::class, ['id' => $this->id])->fetch();
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
            if ($channel->allowInvite()) {
                if ($botperms = $channel->getBotPermissions()) {
                    return $botperms->create_instant_invite;
                }

                return true;
            }

            return false;
        });

        if (! $channel) {
            return reject(new \RuntimeException('No channels found to create an Invite to the specified guild.'));
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
     * @return ExtendedPromiseInterface<Guild> This guild.
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
     *
     * @param bool[]      $features Array of features to set/unset, e.g. `['COMMUNITY' => true, 'INVITES_DISABLED' => false]`.
     * @param string|null $reason   Reason for Audit Log.
     *
     * @return ExtendedPromiseInterface<Guild> This guild.
     *
     * @throws \OutOfRangeException Feature is not mutable.
     * @throws \RuntimeException    Guild feature is already set.
     */
    public function setFeatures(array $features, ?string $reason = null): ExtendedPromiseInterface
    {
        $setFeatures = $this->features;
        foreach ($features as $feature => $set) {
            if (! in_array($feature, ['COMMUNITY', 'INVITES_DISABLED', 'DISCOVERABLE'])) {
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
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/guild#create-guild-json-params
     */
    public function getCreatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->attributes['icon'] ?? null,
            'verification_level' => $this->verification_level,
            'default_message_notifications' => $this->default_message_notifications,
            'explicit_content_filter' => $this->explicit_content_filter,
            'roles' => array_values(array_map(function (Role $role) {
                return $role->getCreatableAttributes();
            }, $this->roles->toArray())) ?: null, // @todo test
            'channels' => array_values(array_map(function (Channel $channel) {
                return $channel->getCreatableAttributes();
            }, $this->channels->toArray())) ?: null, // @todo test
            'afk_channel_id' => $this->afk_channel_id,
            'afk_timeout' => $this->afk_timeout,
            'system_channel_id' => $this->system_channel_id,
            'system_channel_flags' => $this->system_channel_flags,
        ];
    }

    /**
     * @inheritDoc
     *
     * @link https://discord.com/developers/docs/resources/guild#modify-guild-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'name' => $this->name,
            'verification_level' => $this->verification_level ?? null,
            'default_message_notifications' => $this->default_message_notifications ?? null,
            'explicit_content_filter' => $this->explicit_content_filter ?? null,
            'afk_channel_id' => $this->afk_channel_id ?? null,
            'afk_timeout' => $this->afk_timeout,
            'icon' => $this->attributes['icon'] ?? null,
            'splash' => $this->splash_hash,
            'discovery_splash' => $this->attributes['discovery_splash'] ?? null,
            'banner' => $this->attributes['banner'] ?? null,
            'system_channel_id' => $this->system_channel_id ?? null,
            'system_channel_flags' => $this->system_channel_flags,
            'rules_channel_id' => $this->rules_channel_id ?? null,
            'public_updates_channel_id' => $this->public_updates_channel_id ?? null,
            'preferred_locale' => $this->preferred_locale ?? null,
            'description' => $this->description ?? null,
            'premium_progress_bar_enabled' => $this->premium_progress_bar_enabled,
        ];
    }

    /**
     * @inheritDoc
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
