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

trait GuildTrait
{
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
            static $allowed = ['png', 'jpg', 'webp', 'gif'];

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

        static $allowed = ['png', 'jpg', 'webp'];

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
     * Whether the guild has the `ANIMATED_BANNER` feature.
     *
     * @return bool
     */
    protected function getFeatureAnimatedBannerAttribute(): bool
    {
        return in_array('ANIMATED_BANNER', $this->features);
    }

    /**
     * Whether the guild has the `ANIMATED_ICON` feature.
     *
     * @return bool
     */
    protected function getFeatureAnimatedIconAttribute(): bool
    {
        return in_array('ANIMATED_ICON', $this->features);
    }

    /**
     * Whether the guild has the `APPLICATION_COMMAND_PERMISSIONS_V2` feature.
     *
     * @return bool
     */
    protected function getFeatureApplicationCommandPermissionsV2Attribute(): bool
    {
        return in_array('APPLICATION_COMMAND_PERMISSIONS_V2', $this->features);
    }

    /**
     * Whether the guild has the `AUTO_MODERATION` feature.
     *
     * @return bool
     */
    protected function getFeatureAutoModerationAttribute(): bool
    {
        return in_array('AUTO_MODERATION', $this->features);
    }

    /**
     * Whether the guild has the `BANNER` feature.
     *
     * @return bool
     */
    protected function getFeatureBannerAttribute(): bool
    {
        return in_array('BANNER', $this->features);
    }

    /**
     * Whether the guild has the `COMMUNITY` feature.
     *
     * @return bool
     */
    protected function getFeatureCommunityAttribute(): bool
    {
        return in_array('COMMUNITY', $this->features);
    }

    /**
     * Whether the guild has the `CREATOR_MONETIZABLE_PROVISIONAL` feature.
     *
     * @return bool
     */
    protected function getFeatureCreatorMonetizableProvisionalAttribute(): bool
    {
        return in_array('CREATOR_MONETIZABLE_PROVISIONAL', $this->features);
    }

    /**
     * Whether the guild has the `CREATOR_STORE_PAGE` feature.
     *
     * @return bool
     */
    protected function getFeatureCreatorStorePageAttribute(): bool
    {
        return in_array('CREATOR_STORE_PAGE', $this->features);
    }

    /**
     * Whether the guild has the `DEVELOPER_SUPPORT_SERVER` feature.
     *
     * @return bool
     */
    protected function getFeatureDeveloperSupportServerAttribute(): bool
    {
        return in_array('DEVELOPER_SUPPORT_SERVER', $this->features);
    }

    /**
     * Whether the guild has the `DISCOVERABLE` feature.
     *
     * @return bool
     */
    protected function getFeatureDiscoverableAttribute(): bool
    {
        return in_array('DISCOVERABLE', $this->features);
    }

    /**
     * Whether the guild has the `FEATURABLE` feature.
     *
     * @return bool
     */
    protected function getFeatureFeaturableAttribute(): bool
    {
        return in_array('FEATURABLE', $this->features);
    }

    /**
     * Whether the guild has the `HAS_DIRECTORY_ENTRY` feature.
     *
     * @return bool
     */
    protected function getFeatureHasDirectoryEntryAttribute(): bool
    {
        return in_array('HAS_DIRECTORY_ENTRY', $this->features);
    }

    /**
     * Whether the guild has the `INVITES_DISABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureInvitesDisabledAttribute(): bool
    {
        return in_array('INVITES_DISABLED', $this->features);
    }

    /**
     * Whether the guild has the `INVITE_SPLASH` feature.
     *
     * @return bool
     */
    protected function getFeatureInviteSplashAttribute(): bool
    {
        return in_array('INVITE_SPLASH', $this->features);
    }

    /**
     * Whether the guild has the `LINKED_TO_HUB` feature.
     *
     * @return bool
     */
    protected function getFeatureLinkedToHubAttribute(): bool
    {
        return in_array('LINKED_TO_HUB', $this->features);
    }

    /**
     * Whether the guild has the `MEMBER_VERIFICATION_GATE_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureMemberVerificationGateEnabledAttribute(): bool
    {
        return in_array('MEMBER_VERIFICATION_GATE_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `MEMBER_VERIFICATION_MANUAL_APPROVAL` feature.
     *
     * @return bool
     */
    protected function getFeatureMemberVerificationManualApprovalAttribute(): bool
    {
        return in_array('MEMBER_VERIFICATION_MANUAL_APPROVAL', $this->features);
    }

    /**
     * Whether the guild has the `MORE_SOUNDBOARD` feature.
     *
     * @return bool
     */
    protected function getFeatureMoreSoundboardAttribute(): bool
    {
        return in_array('MORE_SOUNDBOARD', $this->features);
    }

    /**
     * Whether the guild has the `MONETIZATION_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureMonetizationEnabledAttribute(): bool
    {
        return in_array('MONETIZATION_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `MORE_STICKERS` feature.
     *
     * @return bool
     */
    protected function getFeatureMoreStickersAttribute(): bool
    {
        return in_array('MORE_STICKERS', $this->features);
    }

    /**
     * Whether the guild has the `NEWS` feature.
     *
     * @return bool
     */
    protected function getFeatureNewsAttribute(): bool
    {
        return in_array('NEWS', $this->features);
    }

    /**
     * Whether the guild has the `PARTNERED` feature.
     *
     * @return bool
     */
    protected function getFeaturePartneredAttribute(): bool
    {
        return in_array('PARTNERED', $this->features);
    }

    /**
     * Whether the guild has the `PREVIEW_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeaturePreviewEnabledAttribute(): bool
    {
        return in_array('PREVIEW_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `PRIVATE_THREADS` feature.
     *
     * @return bool
     */
    protected function getFeaturePrivateThreadsAttribute(): bool
    {
        return in_array('PRIVATE_THREADS', $this->features);
    }

    /**
     * Whether the guild has the `RAID_ALERTS_DISABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureRaidAlertsDisabledAttribute(): bool
    {
        return in_array('RAID_ALERTS_DISABLED', $this->features);
    }

    /**
     * Whether the guild has the `RAID_ALERTS_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureRaidAlertsEnabledAttribute(): bool
    {
        return in_array('RAID_ALERTS_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `ROLE_ICONS` feature.
     *
     * @return bool
     */
    protected function getFeatureRoleIconsAttribute(): bool
    {
        return in_array('ROLE_ICONS', $this->features);
    }

    /**
     * Whether the guild has the `ROLE_SUBSCRIPTIONS_AVAILABLE_FOR_PURCHASE` feature.
     *
     * @return bool
     */
    protected function getFeatureRoleSubscriptionsAvailableForPurchaseAttribute(): bool
    {
        return in_array('ROLE_SUBSCRIPTIONS_AVAILABLE_FOR_PURCHASE', $this->features);
    }

    /**
     * Whether the guild has the `ROLE_SUBSCRIPTIONS_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureRoleSubscriptionsEnabledAttribute(): bool
    {
        return in_array('ROLE_SUBSCRIPTIONS_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `SOUNDBOARD` feature.
     *
     * @return bool
     */
    protected function getFeatureSoundboardAttribute(): bool
    {
        return in_array('SOUNDBOARD', $this->features);
    }

    /**
     * Whether the guild has the `TICKETED_EVENTS_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureTicketedEventsEnabledAttribute(): bool
    {
        return in_array('TICKETED_EVENTS_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `VANITY_URL` feature.
     *
     * @return bool
     */
    protected function getFeatureVanityUrlAttribute(): bool
    {
        return in_array('VANITY_URL', $this->features);
    }

    /**
     * Whether the guild has the `VERIFIED` feature.
     *
     * @return bool
     */
    protected function getFeatureVerifiedAttribute(): bool
    {
        return in_array('VERIFIED', $this->features);
    }

    /**
     * Whether the guild has the `VIP_REGIONS` feature.
     *
     * @return bool
     */
    protected function getFeatureVipRegionsAttribute(): bool
    {
        return in_array('VIP_REGIONS', $this->features);
    }

    /**
     * Whether the guild has the `WELCOME_SCREEN_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureWelcomeScreenEnabledAttribute(): bool
    {
        return in_array('WELCOME_SCREEN_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `GUESTS_ENABLED` feature.
     *
     * @return bool
     */
    protected function getFeatureGuestsEnabledAttribute(): bool
    {
        return in_array('GUESTS_ENABLED', $this->features);
    }

    /**
     * Whether the guild has the `GUILD_TAGS` feature.
     *
     * @return bool
     */
    protected function getFeatureGuildTagsAttribute(): bool
    {
        return in_array('GUILD_TAGS', $this->features);
    }

    /**
     * Whether the guild has the `ENHANCED_ROLE_COLORS` feature.
     *
     * @return bool
     */
    protected function getFeatureEnhancedRoleColorsAttribute(): bool
    {
        return in_array('ENHANCED_ROLE_COLORS', $this->features);
    }

    /**
     * Whether the guild has the `PRUNE_REQUIRES_ADMIN` feature.
     *
     * @return bool
     */
    protected function getFeaturePruneRequiresAdminAttribute(): bool
    {
        return in_array('PRUNE_REQUIRES_ADMIN', $this->features);
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
}
