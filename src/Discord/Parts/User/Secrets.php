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
 * Secrets for Rich Presence joining and spectating.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#activity-object-activity-secrets
 *
 * @property ?string|null $join     Secret for joining a party.
 * @property ?string|null $spectate Secret for spectating a game.
 * @property ?string|null $match    Secret for a specific instanced match.
 */
class Secrets extends Part
{
    /**
     * {@inheritDoc}
     */
    protected $fillable = [
        'join',
        'spectate',
        'match',
    ];
}
