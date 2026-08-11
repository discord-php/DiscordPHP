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
use Discord\WebSockets\Events\Data\GuildJoinRequestUpdateData;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\GuildJoinRequest;

/**
 * Sent when a join request is updated, such as when a user submits a request they had already started, or when a request is approved or rejected.
 * 
 * This event is only sent to bots with the `KICK_MEMBERS` permission.
 * 
 * @see GuildJoinRequest
 * 
 * @link https://docs.discord.com/developers/events/gateway-events#guild-join-request-update
 *
 * @since 10.55.0
 */
class GuildJoinRequestUpdate extends Event
{
    /** @inheritDoc */
    public function handle($data)
    {
        /** @var GuildJoinRequestUpdateData */
        $data = $this->factory->part(GuildJoinRequestUpdateData::class, (array) $data, true);

        /** @var GuildJoinRequest */
        $request = $data->request;

        /** @var Guild $guild */
        if ($guild = yield $this->discord->guilds->cacheGet($request->guild_id)) {
            $guild->join_requests->set($request->id, $request);
        }

        if (isset($request->user)) {
            $this->cacheUser($request->user);
        }

        return $request;
    }
}
