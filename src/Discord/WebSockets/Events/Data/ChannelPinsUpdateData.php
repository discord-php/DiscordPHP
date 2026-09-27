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

namespace Discord\WebSockets\Events\Data;

use Carbon\Carbon;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Sent when a message is pinned or unpinned in a text channel. This is not sent when a pinned message is deleted.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#channel-pins-update
 *
 * @since 10.47.2
 *
 * @property ?string|null $guild_id           ID of the guild.
 * @property string       $channel_id         ID of the channel.
 * @property ?Carbon|null $last_pin_timestamp Time at which the most recent pinned message was pinned.
 *
 * @property Guild|null   $guild   The guild this event belongs to.
 * @property Channel|null $channel The channel this event belongs to.
 */
class ChannelPinsUpdateData extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'guild_id',
        'channel_id',
        'last_pin_timestamp',
    ];

    /**
     * Gets the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($this->guild_id === null) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Gets the channel attribute.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->channel_id === null) {
            return null;
        }

        return $this->discord->getChannel($this->channel_id);
    }

    /**
     * Gets the last_pin_timestamp attribute as Carbon.
     *
     * @return Carbon|null
     *
     * @throws \Exception
     */
    protected function getLastPinTimestampAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('last_pin_timestamp');
    }
}
