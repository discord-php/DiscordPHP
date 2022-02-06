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
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#stage-instance-create
 */
class StageInstanceCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var StageInstance */
        $stageInstancePart = $this->factory->create(StageInstance::class, $data, true);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $guild->stage_instances->pushItem($stageInstancePart);
        }

        $deferred->resolve($stageInstancePart);
    }
}
