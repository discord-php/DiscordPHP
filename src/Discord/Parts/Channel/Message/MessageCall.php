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

namespace Discord\Parts\Channel\Message;

use Discord\Parts\Part;

/**
 * Represents information about a call in a private channel.
 *
 * @link https://discord.com/developers/docs/resources/message#message-call-object
 *
 * @property array       $participants     Array of user object IDs that participated in the call.
 * @property string|null $ended_timestamp  Time when the call ended (ISO8601 timestamp), or null if ongoing.
 */
class MessageCall extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'participants',
        'ended_timestamp',
    ];

    /**
     * Gets the participants attribute.
     *
     * @return array Array of user IDs (snowflakes).
     */
    protected function getParticipantsAttribute(): array
    {
        return $this->attributes['participants'] ?? [];
    }

    /**
     * Gets the ended_timestamp attribute.
     *
     * @return string|null ISO8601 timestamp or null.
     */
    protected function getEndedTimestampAttribute(): ?string
    {
        return $this->attributes['ended_timestamp'] ?? null;
    }
}
