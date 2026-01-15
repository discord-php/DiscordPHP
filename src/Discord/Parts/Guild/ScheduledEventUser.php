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

namespace Discord\Parts\Guild;

use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * A guild scheduled event user subscribed to a specific guild scheduled event exception.
 *
 * @link https://discord.com/developers/docs/resources/guild-scheduled-event#guild-scheduled-event-user-object
 *
 * @since 10.46.0
 *
 * @property string      $guild_scheduled_event_id           The scheduled event id which the user subscribed to.
 * @property User        $user                               User which subscribed to an event.
 * @property Member|null $member                             Guild member data for this user for the guild which this event belongs to, if any.
 * @property string|null $guild_scheduled_event_exception_id The id of the specific scheduled event exception which the user is subscribed to, if any.
 *
 * @property string|null $user_id  ID of the user.
 * @property string|null $guild_id ID of the guild.
 *
 * @property Guild|null $guild The guild this scheduled event user belongs to.
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
        // internal (gateway only)
        'user_id',
        'guild_id',
    ];

    /**
     * Get the user attribute.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if ($this->user_id) {
            return $this->discord->users->get('id', $this->user_id);
        }

        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->attributePartHelper('user', User::class);
    }

    /**
     * Get the member attribute.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        return $this->attributePartHelper('member', Member::class);
    }

    /**
     * Get the guild attribute.
     *
     * @return Guild|null
     */
    protected function getGuildAttribute(): ?Guild
    {
        if ($this->guild_id) {
            return $this->discord->guilds->get('id', $this->guild_id);
        }

        if ($this->member) {
            return $this->member->guild;
        }

        return null;
    }

    /**
     * Get the user_id attribute.
     *
     * @return string|null
     */
    protected function getUserIdAttribute(): ?string
    {
        if (isset($this->attributes['user_id'])) {
            return $this->attributes['user_id'];
        }

        if ($this->user) {
            return $this->user->id;
        }

        return null;
    }

    /**
     * Get the guild_id attribute.
     *
     * @return string|null
     */
    protected function getGuildIdAttribute(): ?string
    {
        if (isset($this->attributes['guild_id'])) {
            return $this->attributes['guild_id'];
        }

        if ($this->guild) {
            return $this->guild->id;
        }

        return null;
    }
}
