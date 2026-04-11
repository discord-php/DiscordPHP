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
use Discord\Parts\Monetization\GameServer;

/**
 * Handles GAME_SERVER_DELETE gateway events.
 *
 * The payload contains a `game_server_id` and a `guild_id`.
 *
 * @todo TBD
 */
class GameServerDelete extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        /** @var GameServer */
        $gameServerPart = $this->factory->part(GameServer::class, ['id' => $data->game_server_id, 'guild_id' => $data->guild_id], true);

        /** @todo No persistent repository exists for game servers yet. */
        return $gameServerPart;
    }
}
