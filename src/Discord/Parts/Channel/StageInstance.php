<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Channel;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;

/**
 * A Stage Instance holds information about a live stage. on a Discord guild.
 *
 * @see https://discord.com/developers/docs/resources/stage-instance#stage-instance-resource
 *
 * @property string       $id                       The unique identifier of the Stage Instance.
 * @property string       $guild_id                 The unique identifier of the guild that the stage instance associated to.
 * @property Guild|null   $guild                    The guild that the stage instance associated to.
 * @property string       $channel_id               The id of the associated Stage channel.
 * @property Channel      $channel                  The channel that the stage instance associated to.
 * @property string       $topic                    The topic of the Stage instance (1-120 characters).
 * @property int          $privacy_level            The privacy level of the Stage instance.
 * @property bool         $send_start_notification  Notify @everyone that a Stage instance has started.
 * @property ?string|null $guild_scheduled_event_id The id of the scheduled event.
 */
class StageInstance extends Part
{
    /** @deprecated 7.0.0 */
    public const PRIVACY_LEVEL_PUBLIC = 1;
    public const PRIVACY_LEVEL_GROUP_ONLY = 2;

    /**
     * @inheritdoc
     */
    protected $fillable = [
        'id',
        'guild_id',
        'channel_id',
        'topic',
        'privacy_level',
        'send_start_notification',
        'discoverable_disabled', // deprecated
        'guild_scheduled_event_id',
    ];

    /**
     * Returns the guild attribute.
     *
     * @return Guild|null The guild attribute.
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->offsetGet($this->guild_id);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|null The Stage channel.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($this->guild && $channel = $this->guild->channels->offsetGet($this->channel_id)) {
            return $channel;
        }

        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getCreatableAttributes(): array
    {
        return [
            'channel_id' => $this->channel_id,
            'topic' => $this->topic,
            'privacy_level' => $this->privacy_level,
            'send_start_notification' => $this->send_start_notification,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getUpdatableAttributes(): array
    {
        return [
            'topic' => $this->topic,
            'privacy_level' => $this->privacy_level,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'channel_id' => $this->channel_id,
        ];
    }
}
