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
use Discord\Parts\Guild\Guild;

use function React\Async\coroutine;

/**
 * @link https://discord.com/developers/docs/topics/gateway#stage-instance-update
 */
class StageInstanceUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $stageInstancePart = $oldStageInstance = null;

            /** @var ?Guild */
            if ($guild = $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?StageInstance */
                if ($oldStageInstance = $guild->stage_instances[$data->id]) {
                    // Swap
                    $stageInstancePart = $oldStageInstance;
                    $oldStageInstance = clone $oldStageInstance;

                    $stageInstancePart->fill((array) $data);
                }
            }

            if ($stageInstancePart === null) {
                /** @var StageInstance */
                $stageInstancePart = $this->factory->create(StageInstance::class, $data, true);
            }

            if ($guild) {
                $guild->stage_instances->cache->set($data->id, $stageInstancePart);
            }

            return [$stageInstancePart, $oldStageInstance];
        }, $data)->then([$deferred, 'resolve']);
    }
}
