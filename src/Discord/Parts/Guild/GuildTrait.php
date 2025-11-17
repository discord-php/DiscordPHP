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

    protected function getFeatureAnimatedBannerAttribute(): bool
    {
        return in_array('ANIMATED_BANNER', $this->features);
    }

    protected function getFeatureAnimatedIconAttribute(): bool
    {
        return in_array('ANIMATED_ICON', $this->features);
    }

    protected function getFeatureApplicationCommandPermissionsV2Attribute(): bool
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

    protected function getFeatureMoreSoundboardAttribute(): bool
    {
        return in_array('MORE_SOUNDBOARD', $this->features);
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

    protected function getFeatureRaidAlertsDisabledAttribute(): bool
    {
        return in_array('RAID_ALERTS_DISABLED', $this->features);
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

    protected function getFeatureSoundboardAttribute(): bool
    {
        return in_array('SOUNDBOARD', $this->features);
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

    protected function getFeatureGuestsEnabledAttribute(): bool
    {
        return in_array('GUESTS_ENABLED', $this->features);
    }

    protected function getFeatureGuildTagsAttribute(): bool
    {
        return in_array('GUILD_TAGS', $this->features);
    }

    protected function getFeatureEnhancedRoleColorsAttribute(): bool
    {
        return in_array('ENHANCED_ROLE_COLORS', $this->features);
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
