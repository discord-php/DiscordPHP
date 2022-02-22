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
 * @see https://discord.com/developers/docs/topics/gateway#stage-instance-update
 */
class StageInstanceUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $stageInstancePart = $oldStageInstance = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldStageInstance = $guild->stage_instances->get('id', $data->id)) {
                // Swap
                $stageInstancePart = $oldStageInstance;
                $oldStageInstance = clone $oldStageInstance;

                $stageInstancePart->fill((array) $data);
            }
        }

        if (! $stageInstancePart) {
            /** @var StageInstance */
            $stageInstancePart = $this->factory->create(StageInstance::class, $data, true);
            if ($guild = $stageInstancePart->guild) {
                $guild->stage_instances->pushItem($stageInstancePart);
            }
        }

        $deferred->resolve([$stageInstancePart, $oldStageInstance]);
    }
}
