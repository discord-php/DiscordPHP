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

namespace Discord\Parts\Gateway;

use Discord\Parts\Part;

/**
 * Used to replay missed events when a disconnected client resumes.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#resume
 *
 * @since 10.56.4
 *
 * @property string $token      Session token.
 * @property string $session_id Session ID.
 * @property int    $seq        Last sequence number received.
 */
class Resume extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'token',
        'session_id',
        'seq',
    ];
}
