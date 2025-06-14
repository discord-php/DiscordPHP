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

namespace Discord\WebSockets;

use JsonSerializable;

/**
 * Represents a Gateway event payload with a voice token.
 *
 * Gateway event payloads have a common structure, but the contents of the associated data (d) varies between the different events.
 *
 * @link https://discord.com/developers/docs/topics/voice-connections#retrieving-voice-server-information-example-voice-server-update-payload
 *
 * @property token
 */
class VoicePayload extends Payload
{
    /** @var string|null */
    protected $token;

    public function __construct(int $op, $d = null, ?int $s = null, ?string $t = null, ?string $token = null)
    {
        $this->op = $op;
        $this->d = $d;
        $this->s = $s;
        $this->t = $t;
        $this->token = $token;
    }

    public static function new(
        int $op,
        $d = null,
        ?int $s = null,
        ?string $t = null,
        ?string $token = null
    ): self
    {
        return new self($op, $d, $s, $t, $token);
    }

    public function setToken(?string $token = null): self
    {
        $this->token = $token;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token ?? null;
    }

    public function jsonSerialize(): array
    {
        $data = parent::jsonSerialize();

        if (isset($this->token)) {
            $data['d']['token'] = $this->token;
        }

        return $data;
    }

    public function __debugInfo()
    {
        $array = parent::__debugInfo();

        if (isset($array['token'])) {
            $array['token'] = 'xxxxx';
        }

        return $array;
    }
}
