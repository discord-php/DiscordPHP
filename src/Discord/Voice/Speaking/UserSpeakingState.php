<?php

declare(strict_types=1);

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
