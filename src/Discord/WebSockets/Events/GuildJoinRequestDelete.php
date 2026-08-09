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

use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\JoinRequest;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\Data\GuildJoinRequestDeleteData;

/**
 * @link https://docs.discord.com/developers/events/gateway-events#guild-join-request-delete
 *
 * @since 10.55.0
 */
class GuildJoinRequestDelete extends Event
{
    /** @inheritDoc */
    public function handle($data)
    {
        /** @var GuildJoinRequestDeleteData */
        $data = $this->factory->part(GuildJoinRequestDeleteData::class, (array) $data, true);

        /** @var Guild $guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var JoinRequest */
            $request = $guild->join_requests->pull($data->id);

            return $request;
        }

        return $this->factory->part(JoinRequest::class, ['id' => $data->id, 'guild_id' => $data->guild_id, 'user_id' => $data->user_id], true);
    }
}
