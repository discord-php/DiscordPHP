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

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use Discord\Parts\Guild\GuildJoinRequest;
use Discord\Parts\Guild\Guild;
use Discord\WebSockets\Events\Data\GuildJoinRequestCreateData;

/**
 * Sent when a user submits a new join request to a guild.
 * 
 * This event is only sent to bots with the `KICK_MEMBERS` permission.
 * 
 * @see GuildJoinRequest
 * 
 * @link https://docs.discord.com/developers/events/gateway-events#guild-join-request-create
 *
 * @since 10.55.0
 */
class GuildJoinRequestCreate extends Event
{
    /** @inheritDoc */
    public function handle($data)
    {
        /** @var GuildJoinRequestCreateData $data */
        $data = $this->factory->part(GuildJoinRequestCreateData::class, (array) $data, true);

        /** @var GuildJoinRequest */
        $request = $data->request;

        if (isset($data->guild_id) && $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var Guild $guild */
            $guild->join_requests->set($request->id, $request);
        }

        if (isset($request->user)) {
            $this->cacheUser($request->user);
        }

        return $request;
    }
}
