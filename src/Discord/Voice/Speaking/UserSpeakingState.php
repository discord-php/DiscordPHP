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

namespace Discord\Voice\Speaking;

final class UserSpeakingState
{
    public int $userId;

    public bool $speaking;

    public array $packets;

    public function __construct(int $userId, bool $speaking, array $packets)
    {
        $this->userId = $userId;
        $this->speaking = $speaking;
        $this->packets = [$this->userId => $packets];
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function isSpeaking(): bool
    {
        return $this->speaking;
    }

    public function getPacketsByUserId(int $userId): array
    {
        return $this->packets[$userId];
    }
}
