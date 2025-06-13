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

namespace Discord\Parts\OAuth;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * Represents an Activity Location.
 *
 * @link https://discord.com/developers/docs/resources/application#get-application-activity-instance-activity-location-object
 *
 * @since 10.17.0
 *
 * @property string      $id         Unique identifier for the location.
 * @property string      $kind       Enum describing kind of location ('gc' or 'pc').
 * @property string      $channel_id ID of the Channel.
 * @property string|null $guild_id   ID of the Guild.
 *
 * @property-read Channel|null $channel
 * @property-read Guild|null   $guild
 */
class ActivityLocation extends Part
{
    /** Location is a Guild Channel */
    public const KIND_GUILD_CHANNEL = 'gc';

    /** Location is a Private Channel (DM or GDM) */
    public const KIND_PRIVATE_CHANNEL = 'pc';

    protected $fillable = [
        'id',
        'kind',
        'channel_id',
        'guild_id',
    ];

    /**
     * Gets the channel part.
     *
     * @return Channel|null
     */
    protected function getChannelAttribute(): ?Channel
    {
        if (!isset($this->attributes['channel_id'])) {
            return null;
        }

        if (isset($this->attributes['guild_id'])) {
            if ($guild = $this->discord->guilds->get('id', $this->attributes['guild_id'])) {
                if ($channel = $guild->channels->get('id', $this->attributes['channel_id'])) {
                    return $channel;
                }
            }
        }

        // @todo potentially slow code
        if ($channel = $this->discord->getChannel($this->attributes['channel_id'])) {
            return $channel;
        }

        return $this->factory->part(Channel::class, ['id' => $this->attributes['channel_id']] + ['guild_id' => $this->attributes['guild_id'] ?? null], true);
    }

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if (!isset($this->attributes['guild_id'])) {
            return null;
        }

        return $this->discord->guilds->get('id', $this->attributes['guild_id']);
    }
}
