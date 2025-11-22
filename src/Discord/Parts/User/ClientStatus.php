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

namespace Discord\Parts\User;

use Discord\Parts\Part;

/**
 * User's platform-dependent status.
 *
 * Active sessions are indicated with an "online", "idle", or "dnd" string per platform. If a user is offline or invisible, the corresponding field is not present.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#client-status-object
 *
 * @property ?string|null $desktop  User's status set for an active desktop (Windows, Linux, Mac) application session.
 * @property ?string|null $mobile   User's status set for an active mobile (iOS, Android) application session.
 * @property ?string|null $web      User's status set for an active web (browser, bot user) application session.
 * @property ?string|null $embedded User's status set for an active embedded application session (Xbox, PlayStation, in-game).
 */
class ClientStatus extends Part
{
    /**
     * @inheritDoc
     */
    protected $fillable = [
        'desktop',
        'mobile',
        'web',
        'embedded',
    ];
}
