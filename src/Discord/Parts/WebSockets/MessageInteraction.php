<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Parts\WebSockets;

use Discord\Parts\Part;
use Discord\Parts\User\Member;
use Discord\Parts\User\User;

/**
 * Sent on the message object when the message is a response to an Interaction
 * without an existing message.
 *
 * @link https://discord.com/developers/docs/interactions/receiving-and-responding#message-interaction-object
 *
 * @since 7.1.0
 *
 * @property string      $id     ID of the interaction.
 * @property int         $type   Type of interaction.
 * @property string      $name   Name of the application command, including subcommands and subcommand groups.
 * @property User        $user   User who invoked the interaction.
 * @property Member|null $member Partial Member who invoked the interaction.
 *
 * @property string|null $guild_id
 */
class MessageInteraction extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'id',
        'type',
        'name',
        'user',
        'member',

        // @internal
        'guild_id',
    ];

    /**
     * {@inheritDoc}
     */
    protected $hidden = ['guild_id'];

    /**
     * Returns the user who invoked the interaction.
     *
     * @return User
     */
    protected function getUserAttribute(): User
    {
        if ($user = $this->discord->users->get('id', $this->attributes['user']->id)) {
            return $user;
        }

        return $this->factory->part(User::class, (array) $this->attributes['user'], true);
    }

    /**
     * Returns the partial Member who invoked the interaction.
     *
     * @return Member|null
     */
    protected function getMemberAttribute(): ?Member
    {
        if (isset($this->guild_id)) {
            if ($guild = $this->discord->guilds->get('id', $this->guild_id)) {
                if ($member = $guild->members->get('id', $this->attributes['member']->id ?? $this->user->id)) {
                    return $member;
                }
            }

            if (isset($this->attributes['member'])) {
                return $this->factory->part(Member::class, (array) $this->attributes['member'] + ['guild_id' => $this->guild_id], true);
            }
        }

        return null;
    }
}
