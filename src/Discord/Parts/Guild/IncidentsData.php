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
 * The incidents data for a guild.
 *
 * @link https://discord.com/developers/docs/resources/guild#incidents-data-object
 *
 * @since 10.29.0
 *
 * @property ?Carbon|null $invites_disabled_until When invites get enabled again (ISO8601 timestamp).
 * @property ?Carbon|null $dms_disabled_until     When direct messages get enabled again (ISO8601 timestamp).
 * @property ?Carbon|null $dm_spam_detected_at    When the DM spam was detected (ISO8601 timestamp).
 * @property ?Carbon|null $raid_detected_at       When the raid was detected (ISO8601 timestamp).
 */
class IncidentsData extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'invites_disabled_until',
        'dms_disabled_until',
        'dm_spam_detected_at',
        'raid_detected_at',
    ];

    /**
     * Get the invites_disabled_until attribute.
     *
     * @return Carbon|null
     */
    protected function getInvitesDisabledUntilAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('invites_disabled_until');
    }

    /**
     * Get the dms_disabled_until attribute.
     *
     * @return Carbon|null
     */
    protected function getDmsDisabledUntilAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('dms_disabled_until');
    }

    /**
     * Get the dm_spam_detected_at attribute.
     *
     * @return Carbon|null
     */
    protected function getDmSpamDetectedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('dm_spam_detected_at');
    }

    /**
     * Get the raid_detected_at attribute.
     *
     * @return Carbon|null
     */
    protected function getRaidDetectedAtAttribute(): ?Carbon
    {
        return $this->attributeCarbonHelper('raid_detected_at');
    }
}
