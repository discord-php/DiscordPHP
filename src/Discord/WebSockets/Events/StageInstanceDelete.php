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
        $stageinstance = $this->factory->create(StageInstance::class, $data);

        if ($guild = $stageinstance->guild) {
            $guild->stageinstances->pull($stageinstance->id);

            $this->discord->guilds->push($guild);
        }

        $deferred->resolve($stageinstance);
    }
}
