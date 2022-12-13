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
use Discord\Parts\Channel\StageInstance;
use Discord\WebSockets\Event;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#stage-instance-create
 *
 * @since 7.0.0
 */
class StageInstanceCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var StageInstance */
        $stageInstancePart = $this->factory->part(StageInstance::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if ($channel = yield $guild->channels->cacheGet($data->channel_id)) {
                $channel->stage_instances->set($data->id, $stageInstancePart);
            }
        }

        return $stageInstancePart;
    }
}
