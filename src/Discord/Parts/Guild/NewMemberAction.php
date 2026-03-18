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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Part;

/**
 * An action taken for a new member in the welcome message.
 *
 * @link https://github.com/discord/discord-api-spec/blob/7cba79e03a393456fc904cff470097d3be383bec/specs/openapi_preview.json#L32111
 *
 * @since 10.47.0 OpenAPI Preview
 *
 * @property string|null $channel_id  The target channel id.
 * @property int         $action_type The action type (0 = VIEW, 1 = TALK).
 * @property string      $title       The action title.
 * @property string      $description The action description.
 * @property Emoji|null  $emoji       The emoji associated with the action.
 * @property string|null $icon        The icon string, if present.
 *
 * @property-read string|null  $guild_id The guild id associated with this action.
 * @property-read Guild|null   $guild    The guild associated with this action.
 * @property-read Channel|null $channel  The channel associated with this action.
 */
class NewMemberAction extends Part
{
    public const ACTION_TYPE_VIEW = 0;
    public const ACTION_TYPE_TALK = 1;

    /**
     * @inheritDoc
     */
    protected $fillable = [
        'channel_id',
        'action_type',
        'title',
        'description',
        'emoji',
        'icon',

        // @internal
        'guild_id',
    ];

    /**
     * Gets the emoji.
     *
     * @return Emoji|null
     */
    protected function getEmojiAttribute(): ?Emoji
    {
        return $this->attributePartHelper('emoji', Emoji::class);
    }

    /**
     * Gets the guild.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        return $this->discord->channels->get('id', $this->attributes['channel_id']);
    }
}
