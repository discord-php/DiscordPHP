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
 * Represents the onboarding flow for a guild.
 *
 * @since 10.26.0
 *
 * @link https://docs.discord.com/developers/resources/guild#guild-onboarding-object
 *
 * @property string                                                     $guild_id            ID of the guild this onboarding is part of
 * @property ExCollectionInterface<OnboardingPrompt>|OnboardingPrompt[] $prompts             Prompts shown during onboarding and in customize community
 * @property string[]                                                   $default_channel_ids Channel IDs that members get opted into automatically
 * @property bool                                                       $enabled             Whether onboarding is enabled in the guild
 * @property int                                                        $mode                Current mode of onboarding
 *
 * @property-read Guild|null $guild The guild.
 */
class Onboarding extends Part
{
    public const MODE_ONBOARDING_DEFAULT = 0;
    public const MODE_ONBOARDING_ADVANCED = 1;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_id',
        'prompts',
        'default_channel_ids',
        'enabled',
        'mode',
    ];

    /**
     * Get the prompts for this onboarding.
     *
     * @return ExCollectionInterface<OnboardingPrompt>|OnboardingPrompt[]
     */
    protected function getPromptsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('prompts', OnboardingPrompt::class, 'id', ['guild_id' => $this->guild_id]);
    }

    /**
     * Returns the guild which the onboarding belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}
