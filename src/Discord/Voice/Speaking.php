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

namespace Discord\Voice;

use Discord\Parts\Part;
use Discord\Parts\User\User;

/**
 * Represents a user's speaking state in a voice channel.
 *
 * @link https://discord.com/developers/docs/topics/voice-connections#speaking
 *
 * @since 10.40.0
 *
 * @property      string|null $user_id  The user ID of the user that is speaking, or null if this bot is speaking.
 * @property-read User|null   $user     The user that is speaking.
 * @property      int         $ssrc     The SSRC identifier for the user.
 * @property      bool        $speaking Whether the user is speaking or not.
 * @property      int         $delay    The delay property should be set to 0 for bots that use the voice gateway.
 */
class Speaking extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'user_id',
        'ssrc',
        'speaking',

        // internal
        'delay',
    ];

    /**
     * Get the user attribute.
     *
     * @return User|null The user.
     */
    protected function getUserAttribute(): ?User
    {
        if (! isset($this->attributes['user_id'])) {
            return $this->discord->user;
        }

        return $this->discord->users->get('id', $this->attributes['user_id']);
    }

    /**
     * Get the speaking attribute.
     * 
     * @return bool Whether the user is speaking.
     */
    protected function getSpeakingAttribute(): bool
    {
        return (bool) $this->attributes['speaking'];
    }

    /**
     * Get the delay attribute.
     *
     * @return int The delay.
     */
    protected function getDelayAttribute(): int
    {
        return $this->attributes['delay'] ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        $body = [
            'ssrc' => $this->ssrc,
            'speaking' => $this->speaking,
        ];

        if (isset($this->attributes['user_id'])) {
            $body['user_id'] = $this->attributes['user_id'];
        }

        if (isset($this->attributes['delay'])) {
            $body['delay'] = $this->attributes['delay'];
        }

        return $body;
    }
}
