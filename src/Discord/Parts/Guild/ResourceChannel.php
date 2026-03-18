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
 * Resource channel in a server guide.
 *
 * @link https://github.com/discord/discord-api-spec/blob/7cba79e03a393456fc904cff470097d3be383bec/specs/openapi_preview.json#L34147
 *
 * @since 10.47.0 OpenAPI Preview
 *
 * @property string      $channel_id  The channel id for the resource.
 * @property string      $title       The title of the resource channel.
 * @property Emoji|null  $emoji       The partial emoji for the resource.
 * @property string|null $icon        The icon string, if present.
 * @property string      $description The description of the resource channel.
 *
 * @property-read string|null  $guild_id The guild id associated with this resource channel.
 * @property-read Guild|null   $guild    The guild associated with this resource channel.
 * @property-read Channel|null $channel  The channel associated with this resource channel.
 */
class ResourceChannel extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'channel_id',
        'title',
        'emoji',
        'icon',
        'description',

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
        return $this->discord->channels->get('id', $this->channel_id);
    }
}
