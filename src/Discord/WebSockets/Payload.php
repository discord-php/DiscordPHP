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
 * Represents a Gateway event payload.
 *
 * Gateway event payloads have a common structure, but the contents of the associated data (d) varies between the different events.
 *
 * @link https://discord.com/developers/docs/topics/gateway#payloads-gateway-payload-structure
 *
 * @property int         $op Gateway opcode, which indicates the payload type.
 * @property mixed|null  $d  Event data.
 * @property int|null    $s  Sequence number of event used for resuming sessions and heartbeating.
 * @property string|null $t  Event name.
 */
class Payload implements JsonSerializable
{
    /** @var int */
    public $op;

    /** @var mixed|null */
    public $d;

    /** @var int|null */
    public $s;

    /** @var string|null */
    public $t;

    public function __construct(int $op, $d = null, ?int $s = null, ?string $t = null)
    {
        $this->op = $op;
        $this->d = $d;
        $this->s = $s;
        $this->t = $t;
    }

    public static function new(
        int $op,
        $d = null,
        ?int $s = null,
        // token - add attribute
        ?string $t = null
    ): self
    {
        return new self($op, $d, $s, $t);
    }

    public function jsonSerialize(): array
    {
        $data['op'] = $this->op;
        $data['d'] = $this->d ?? [];
        if (isset($this->s)) {
            $data['s'] = $this->s;
        }
        if (isset($this->t)) {
            $data['t'] = $this->t;
        }
        return $data;
    }

    public function __debugInfo()
    {
        $array = $this->jsonSerialize();

        if (isset($array['d']['token'])) {
            $array['d']['token'] = 'xxxxx';
        }

        return $array;
    }
}
