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

use Discord\Parts\Guild\GuildJoinRequest;
use Discord\Parts\Part;

/**
 * Raw data received from the `GUILD_JOIN_REQUEST_CREATE` event.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#guild-join-request-create
 *
 * @since 10.55.0
 *
 * @property string           $guild_id ID of the guild
 * @property string           $status   Application status of the join request
 * @property GuildJoinRequest $request  The join request that was created
 */
class GuildJoinRequestCreateData extends Part
{
    /** @inheritDoc */
    protected $fillable = [
        'guild_id',
        'status',
        'request',
    ];

    /**
     * Gets the request attribute as a `GuildJoinRequest` part.
     *
     * @return GuildJoinRequest|null
     */
    protected function getRequestAttribute(): ?GuildJoinRequest
    {
        return $this->attributePartHelper('request', GuildJoinRequest::class);
    }
}
