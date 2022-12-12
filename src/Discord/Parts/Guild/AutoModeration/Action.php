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
 * An action which will execute whenever a rule is triggered.
 *
 * @link https://discord.com/developers/docs/resources/auto-moderation#auto-moderation-action-object
 *
 * @since 7.1.0
 *
 * @property int         $type     The type of action.
 * @property object|null $metadata Additional metadata needed during execution for this specific action type (may contain `channel_id` and `duration_seconds`).
 */
class Action extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'type',
        'metadata',
    ];

    public const TYPE_BLOCK_MESSAGE = 1;
    public const TYPE_SEND_ALERT_MESSAGE = 2;
    public const TYPE_TIMEOUT = 3;

    /**
     * {@inheritDoc}
     *
     * @see Rule::getCreatableAttributes()
     */
    public function getCreatableAttributes(): array
    {
        $attr = [
            'type' => $this->type,
        ];

        if (isset($this->attributes['metadata'])) {
            $attr['metadata'] = $this->metadata;
        }

        return $attr;
    }
}
