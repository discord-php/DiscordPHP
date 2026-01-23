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

namespace Discord\WebSockets\Events\Data;

use Discord\Parts\Part;

/**
 * @link https://discord.com/developers/docs/events/gateway-events#invite-delete-invite-delete-event-fields
 *
 * @since 10.46.0
 * 
 * @property string|null $channel_id Channel of the invite.
 * @property string|null $guild_id   Guild of the invite.
 * @property string $code       Unique invite code.
 */
class InviteDeleteData extends Part
{
    protected $fillable = [
        'channel_id',
        'guild_id',
        'code',
    ];
}
