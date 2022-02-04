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

class StageInstanceDelete extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $stageInstance = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($stageInstance = $guild->stage_instances->pull($data->id)) {
                $stageInstance->fill((array) $data);
                $stageInstance->created = false;
            }
        }

        if (! $stageInstance) {
            /** @var StageInstance */
            $stageInstance = $this->factory->create(StageInstance::class, $data);
        }

        $deferred->resolve($stageInstance);
    }
}
