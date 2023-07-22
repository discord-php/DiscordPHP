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
 * A Stage Instance holds information about a live stage.
 *
 * @link https://discord.com/developers/docs/resources/stage-instance#stage-instance-resource
 *
 * @since 7.0.0
 *
 * @property       string       $id                       The unique identifier of the Stage Instance.
 * @property       string       $guild_id                 The unique identifier of the guild that the stage instance associated to.
 * @property-read  Guild|null   $guild                    The guild that the stage instance associated to.
 * @property       string       $channel_id               The id of the associated Stage channel.
 * @property-read  Channel|null $channel                  The channel that the stage instance associated to.
 * @property       string       $topic                    The topic of the Stage instance (1-120 characters).
 * @property       int          $privacy_level            The privacy level of the Stage instance.
 * @property-write bool|null    $send_start_notification  Notify @everyone that a Stage instance has started.
 * @property       ?string      $guild_scheduled_event_id The id of the scheduled event.
 */
class StageInstance extends Part
{
    public const PRIVACY_LEVEL_GROUP_ONLY = 2;

    /**
     * {@inheritDoc}
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
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Returns the channel attribute.
     *
     * @return Channel|null The Stage channel.
     */
    protected function getChannelAttribute(): ?Channel
    {
        if ($guild = $this->guild) {
            if ($channel = $guild->channels->get('id', $this->channel_id)) {
                return $channel;
            }
        }

        if ($channel = $this->discord->getChannel($this->channel_id)) {
            return $channel;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/stage-instance#create-stage-instance-json-params
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'channel_id' => $this->channel_id,
            'topic' => $this->topic,
        ];

        $attr += $this->makeOptionalAttributes([
            'privacy_level' => $this->privacy_level,
            'send_start_notification' => $this->send_start_notification,
            'guild_scheduled_event_id' => $this->guild_scheduled_event_id,
        ]);

        return $attr;
    }

    /**
     * {@inheritDoc}
     *
     * @link https://discord.com/developers/docs/resources/stage-instance#modify-stage-instance-json-params
     */
    public function getUpdatableAttributes(): array
    {
        return $this->makeOptionalAttributes([
            'topic' => $this->topic,
            'privacy_level' => $this->privacy_level,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getRepositoryAttributes(): array
    {
        return [
            'channel_id' => $this->channel_id,
        ];
    }
}
