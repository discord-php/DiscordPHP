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

use Carbon\Carbon;
use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents information about a call in a private channel.
 *
 * @since 10.11.2
 *
 * @link https://discord.com/developers/docs/resources/message#message-call-object
 *
 * @property array        $participants      Array of user object IDs that participated in the call.
 * @property ?Carbon|null $ended_timestamp   Time when the call ended (ISO8601 timestamp), or null if ongoing.
 *
 * @property-read User[]  $users Array of user objects that participated in the call.
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
     * Gets the ended timestamp.
     *
     * @return Carbon|null
     */
    protected function getEndedTimestampAttribute(): ?Carbon
    {
        if (!isset($this->attributes['ended_timestamp'])) {
            return null;
        }

        return Carbon::parse($this->attributes['ended_timestamp']);
    }

    /**
     * Gets the users.
     *
     * @return User[]
     */
    protected function getUsersAttribute(): array
    {
        return array_map(
            fn($userData) => $this->discord->users->get('id', $userData) ?? $this->factory->create(User::class, ['id' => $userData], true),
            $this->attributes['participants']
        );
    }
}
