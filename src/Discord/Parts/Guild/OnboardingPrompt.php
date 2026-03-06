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
 * Represents an onboarding prompt for a guild.
 *
 * @since 10.26.0
 *
 * @link https://docs.discord.com/developers/resources/guild#guild-onboarding-object-onboarding-prompt-structure
 *
 * @property string                                                                 $id            ID of the prompt.
 * @property string                                                                 $type          Type of prompt.
 * @property ExCollectionInterface<OnboardingPromptOption>|OnboardingPromptOption[] $options       Options available within the prompt.
 * @property string                                                                 $title         Title of the prompt.
 * @property bool                                                                   $single_select Indicates whether users are limited to selecting one option for the prompt.
 * @property bool                                                                   $required      Indicates whether the prompt is required before a user completes the onboarding flow.
 * @property bool                                                                   $in_onboarding Indicates whether the prompt is present in the onboarding flow. If false, the prompt will only appear in the Channels & Roles tab.
 */
class OnboardingPrompt extends Part
{
    public const TYPE_MULTIPLE_CHOICE = 0;
    public const TYPE_DROPDOWN = 1;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'type',
        'options',
        'title',
        'single_select',
        'required',
        'in_onboarding',

        // @internal
        'guild_id',
    ];

    /**
     * Get the options for this prompt.
     *
     * @return ExCollectionInterface<OnboardingPromptOption>|OnboardingPromptOption[] An array of OnboardingPromptOption objects representing the options for this prompt.
     */
    public function getOptionsAttribute(): ExCollectionInterface
    {
        return $this->attributeCollectionHelper('options', OnboardingPromptOption::class, 'id', ['guild_id' => $this->guild_id]);
    }
}
