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

/**
 * Represents the hello data sent when establishing a voice connection.
 *
 * @link https://discord.com/developers/docs/topics/voice-connections#heartbeating-example-hello-payload
 *
 * @since 10.40.0
 *
 * @property int $v                  The voice gateway version.
 * @property int $heartbeat_interval The heartbeat interval in milliseconds.
 */
class Hello extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'v',
        'heartbeat_interval',
    ];
}
