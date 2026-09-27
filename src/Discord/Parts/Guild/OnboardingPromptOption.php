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

use Discord\Parts\Part;

/**
 * Represents an onboarding prompt option for a guild.
 *
 * When creating or updating a prompt option, the emoji_id, emoji_name, and emoji_animated fields must be used instead of the emoji object.
 *
 * @since 10.26.0
 *
 * @link https://docs.discord.com/developers/resources/guild#guild-onboarding-object-prompt-option-structure
 *
 * @property string       $id             ID of the prompt option.
 * @property array        $channel_ids    IDs for channels a member is added to when the option is selected.
 * @property array        $role_ids       IDs for roles assigned to a member when the option is selected.
 * @property ?Emoji|null  $emoji          Emoji of the option.
 * @property ?string|null $emoji_id       Emoji ID of the option.
 * @property ?string|null $emoji_name     Emoji name of the option.
 * @property ?bool|null   $emoji_animated Whether the emoji is animated.
 * @property string       $title          Title of the option.
 * @property string|null  $description    Description of the option.
 *
 * @property-read string|null $guild_id The ID of the guild.
 * @property-read Guild|null  $guild    The guild.
 */
class OnboardingPromptOption extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'channel_ids',
        'role_ids',
        'emoji',
        'emoji_id',
        'emoji_name',
        'emoji_animated',
        'title',
        'description',

        // @internal
        'guild_id',
    ];

    /**
     * Returns the emoji for this prompt option.
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        if ($this->guild) {
            return $this->guild->emojis->get('id', $this->emoji_id ?? $this->emoji->id)
                ?? $this->guild->emojis->get('name', $this->emoji_name ?? $this->emoji->name);
        }

        return $this->attributePartHelper('emoji', Emoji::class);
    }

    /**
     * Returns the guild which the onboarding prompt option belongs to.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }
}
