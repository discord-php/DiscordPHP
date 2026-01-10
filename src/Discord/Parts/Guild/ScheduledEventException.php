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

namespace Discord\Parts\Guild;

use Carbon\Carbon;
use Discord\Parts\Part;

/**
 * A guild scheduled event exception represents a skipped or rescheduled recurrence for the scheduled event's recurrence rule. In the client, scheduled event exceptions are commonly known as "canceled events" or "re-scheduled" events.
 *
 * @link https://discord.com/developers/docs/resources/guild-scheduled-event#guild-scheduled-event-exception-object
 *
 * @since 10.45.0
 *
 * @property string  $event_id             The id of the scheduled event.
 * @property string  $event_exception_id   A snowflake containing the original scheduled start time of the scheduled event. The snowflake in this field is not guaranteed to be globally unique.
 * @property Carbon  $scheduled_start_time The new time at when the scheduled event recurrence will start, if applicable.
 * @property ?Carbon $scheduled_end_time   The new time at when the scheduled event recurrence will end, if applicable.
 * @property bool    $is_canceled          Whether or not the scheduled event will be skipped on the recurrence.
 *
 * @property string $guild_id The id of the guild the scheduled event belongs to.
 */
class ScheduledEventException extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'event_id',
        'event_exception_id',
        'scheduled_start_time',
        'scheduled_end_time',
        'is_canceled',
        // internal
        'guild_id',
    ];

    /**
     * Returns the Scheduled Start Time attribute.
     *
     * @return Carbon
     */
    protected function getScheduledStartTimeAttribute(): Carbon
    {
        return $this->attributeCarbonHelper('scheduled_start_time');
    }

    /**
     * Returns the Scheduled End Time attribute.
     *
     * @return ?Carbon
     */
    protected function getScheduledEndTimeAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('scheduled_end_time');
    }
}
