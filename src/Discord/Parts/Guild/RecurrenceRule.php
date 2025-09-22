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

use Discord\Parts\Part;

/**
 * Discord's recurrence rule is a subset of the behaviors defined in the iCalendar RFC and implemented by python's dateutil rrule.
 *
 * @link https://discord.com/developers/docs/resources/guild-scheduled-event#guild-scheduled-event-recurrence-rule-object
 *
 * @since 10.24.0
 *
 * @property string  $id           The unique identifier of the guild.
 * @property string  $start        Starting time of the recurrence interval (ISO8601 timestamp).
 * @property ?string $end          Ending time of the recurrence interval (ISO8601 timestamp).
 * @property array   $frequency    How often the event occurs (recurrence rule - frequency object).
 * @property int     $interval     The spacing between the events, defined by frequency.
 * @property ?array  $by_weekday   Set of specific days within a week for the event to recur on.
 * @property ?array  $by_n_weekday List of specific days within a specific week (1-5) to recur on.
 * @property ?array  $by_month     Set of specific months to recur on.
 * @property ?array  $by_month_day Set of specific dates within a month to recur on.
 * @property ?array  $by_year_day  Set of days within a year to recur on (1-364).
 * @property ?int    $count        The total amount of times that the event is allowed to recur before stopping.
 */
class RecurrenceRule extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'id',
        'start',
        'end',
        'frequency',
        'interval',
        'by_weekday',
        'by_n_weekday',
        'by_month',
        'by_month_day',
        'by_year_day',
        'count',
    ];
}
