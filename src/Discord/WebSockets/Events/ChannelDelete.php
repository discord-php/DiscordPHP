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

use Discord\Builders\ChannelBuilder;
use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#channel-delete
 *
 * @since 2.1.3
 */
class ChannelDelete extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        $channelPart = null;

        /** @var ?\Discord\Parts\Guild\Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var Channel */
            if ($channelPart = yield $guild->channels->cachePull($data->id)) {
                $channelPart->fill((array) $data);
                $channelPart->created = false;
            }
        }

        return $channelPart ?? $this->factory->part(ChannelBuilder::TYPES[$data->type] ?? Channel::class, (array) $data);
    }
}
