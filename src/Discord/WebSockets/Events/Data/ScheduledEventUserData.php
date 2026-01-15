<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events\Data;

use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Sent when a user has subscribed or unsubscribed to a guild scheduled event.
 *
 * @see Discord\Parts\Guild\ScheduledEventUser
 *
 * @link https://discord.com/developers/docs/events/gateway-events.mdx#guild-scheduled-event-user-add-event-fields
 *
 * @since 10.46.0
 *
 * @property string      $guild_scheduled_event_id           ID of the guild scheduled event
 * @property string      $user_id                            ID of the user.
 * @property string      $guild_id                           ID of the guild.
 * @property string|null $guild_scheduled_event_exception_id ID of the guild scheduled event exception, if applicable.
 *
 * @property User|null   $user   User which subscribed to an event.
 * @property Guild|null  $guild  The guild this scheduled event user data belongs to.
 * @property Member|null $member Guild member data for this user for the guild which this event belongs to, if any.
 */
class ScheduledEventUserData extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'guild_scheduled_event_id',
        'user_id',
        'guild_id',
        'guild_scheduled_event_exception_id',
    ];

    /**
     * Get the user attribute.
     *
     * @return User|null
     */
    protected function getUserAttribute(): ?User
    {
        return $this->discord->users->get('id', $this->user_id);
    }

    /**
     * Get the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        return $this->discord->guilds->get('id', $this->guild_id);
    }

    /**
     * Get the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if ($guild = $this->guild) {
            return $guild->members->get('id', $this->user_id);
        }

        return null;
    }
}
