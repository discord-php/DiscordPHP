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
 * @see https://discord.com/developers/docs/topics/gateway#stage-instance-delete
 */
class StageInstanceDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $stageInstancePart = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($stageInstancePart = $guild->stage_instances->pull($data->id)) {
                $stageInstancePart->fill((array) $data);
                $stageInstancePart->created = false;
            }
        }

        if (! $stageInstancePart) {
            /** @var StageInstance */
            $stageInstancePart = $this->factory->create(StageInstance::class, $data);
        }

        $deferred->resolve($stageInstancePart);
    }
}
