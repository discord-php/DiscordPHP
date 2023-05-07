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

use Discord\Parts\Metadata;

/**
 * Additional data used when an action is executed. Different fields are
 * relevant based on the value of action types.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-action-object-action-metadata
 *
 * @since 10.0.0
 */
class ActionMetadata extends Metadata
{
    /**
     * Channel to which user content should be logged.
     *
     * @see Action::TYPE_SEND_ALERT_MESSAGE
     */
    public string $channel_id;

    /**
     * Timeout duration in seconds.
     * Maximum of 2419200 seconds (4 weeks).
     * 
     * @see Action::TYPE_TIMEOUT
     */
    public int $duration_seconds;

    /**
     * Additional explanation that will be shown to members whenever their
     * message is blocked.
     * Maximum of 150 characters.
     * 
     * @see Action::TYPE_BLOCK_MESSAGE
     */
    public string $custom_message;
}
