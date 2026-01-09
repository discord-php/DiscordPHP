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
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * A guild scheduled event user subscribed to a specific guild scheduled event exception.
 *
 * @link https://discord.com/developers/docs/resources/guild-scheduled-event#guild-scheduled-event-user-object
 *
 * @since 10.45.0
 *
 * @property string      $guild_scheduled_event_id           The scheduled event id which the user subscribed to.
 * @property User        $user                               User which subscribed to an event.
 * @property Member|null $member                             Guild member data for this user for the guild which this event belongs to, if any.
 * @property string|null $guild_scheduled_event_exception_id The id of the specific scheduled event exception which the user is subscribed to, if any.
 */
class ScheduledEventUser extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_scheduled_event_id',
        'user',
        'member',
        'guild_scheduled_event_exception_id',
    ];

    /**
     * Get the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Get the member attribute.
     *
     * @return ?Member
     */
    protected function getMemberAttribute(): ?Member
    {
        return $this->attributePartHelper('member', Member::class);
    }
}
