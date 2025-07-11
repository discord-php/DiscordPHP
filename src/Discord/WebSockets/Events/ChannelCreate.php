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

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\WebSockets\Event;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#channel-create
 *
 * @since 2.1.3
 */
class ChannelCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Channel */
        $channelPart = $this->factory->part(Channel::class, (array) $data, true);

        if ($channelPart->is_private) {
            $this->discord->private_channels->set($data->id, $channelPart);
        } else {
            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                $guild->channels->set($data->id, $channelPart);
            }
        }

        return $channelPart;
    }
}
