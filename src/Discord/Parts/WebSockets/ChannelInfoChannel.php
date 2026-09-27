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

namespace Discord\Parts\WebSockets;

use Discord\Parts\Part;

/**
 * Represents ephemeral channel info returned by the gateway.
 *
 * @since 10.48.0
 *
 * @property string       $id
 * @property ?string|null $status
 * @property ?int|null    $voice_start_time
 */
class ChannelInfoChannel extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'id',
        'status',
        'voice_start_time',
    ];
}
