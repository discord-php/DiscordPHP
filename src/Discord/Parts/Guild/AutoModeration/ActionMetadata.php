<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\Guild\AutoModeration;

use Discord\Parts\Part;

/**
 * Additional data used when an action is executed. Different fields are
 * relevant based on the value of action types.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-action-object-action-metadata
 *
 * @since 10.0.0
 *
 * @property string      $channel_id       Channel to which user content should be logged. For `SEND_ALERT_MESSAGE`.
 * @property int         $duration_seconds Timeout duration in seconds. Maximum of 2419200 seconds (4 weeks). For `TYPE_TIMEOUT`.
 * @property string|null $custom_message   Additional explanation that will be shown to members whenever their message is blocked. Maximum of 150 characters. For `TYPE_BLOCK_MESSAGE`.
 */
class ActionMetadata extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'channel_id',
        'duration_seconds',
        'custom_message',
    ];

    /**
     * {@inheritDoc}
     */
    public function getPublicAttributes(): array
    {
        // Return raw as the attributes might only contain one of the fillable
        return $this->getRawAttributes();
    }
}
