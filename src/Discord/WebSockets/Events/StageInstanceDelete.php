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
 * @link https://discord.com/developers/docs/topics/gateway#stage-instance-delete
 *
 * @since 7.0.0
 */
class StageInstanceDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        coroutine(function ($data) {
            $stageInstancePart = null;

            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                /** @var ?StageInstance */
                if ($stageInstancePart = yield $guild->stage_instances->cachePull($data->id)) {
                    $stageInstancePart->fill((array) $data);
                    $stageInstancePart->created = false;
                }
            }

            return $stageInstancePart ?? $this->factory->create(StageInstance::class, $data);
        }, $data)->then([$deferred, 'resolve']);
    }
}
