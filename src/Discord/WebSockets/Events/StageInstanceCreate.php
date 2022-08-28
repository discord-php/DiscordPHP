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

use Discord\Parts\Channel\StageInstance;
use Discord\WebSockets\Event;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway#stage-instance-create
 *
 * @since 7.0.0
 */
class StageInstanceCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle($data)
    {
            /** @var StageInstance */
            $stageInstancePart = $this->factory->create(StageInstance::class, $data, true);

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                yield $guild->stage_instances->cache->set($data->id, $stageInstancePart);
            }

            return $stageInstancePart;
    }
}
