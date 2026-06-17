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

namespace Discord\MessageCommandClient;

use Discord\Parts\Channel\Message;

class CooldownManager
{
    /**
     * @var array<string,int>
     */
    protected array $cooldowns = [];

    /**
     * Enforce and update cooldowns for a message author.
     *
     * @param Message $message
     * @param int     $currentTime
     * @param int     $cooldownMs
     * @param string  $cooldownMessage
     *
     * @return string|null
     */
    public function enforce(Message $message, int $currentTime, int $cooldownMs, string $cooldownMessage): ?string
    {
        if ($cooldownMs <= 0) {
            return null;
        }

        $userId = $message->author->id;

        if (isset($this->cooldowns[$userId])) {
            if ($this->cooldowns[$userId] < $currentTime) {
                $this->cooldowns[$userId] = $currentTime + $cooldownMs;

                return null;
            }

            return sprintf($cooldownMessage, (($this->cooldowns[$userId] - $currentTime) / 1000));
        }

        $this->cooldowns[$userId] = $currentTime + $cooldownMs;

        return null;
    }
}
