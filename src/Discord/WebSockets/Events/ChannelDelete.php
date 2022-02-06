<?php

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
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#channel-delete
 */
class ChannelDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var Channel */
        $channelPart = $this->factory->create(Channel::class, $data);

        if ($guild = $channelPart->guild) {
            if ($channelPart = $guild->channels->pull($data->id)) {
                $channelPart->fill((array) $data);
                $channelPart->created = false;
            }
        }

        $deferred->resolve($channelPart);
    }
}
