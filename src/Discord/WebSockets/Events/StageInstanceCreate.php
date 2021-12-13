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

class StageInstanceCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var StageInstance */
        $stageinstance = $this->factory->create(StageInstance::class, $data, true);

        if ($guild = $this->discord->guilds->get('id', $stageinstance->guild_id)) {
            $guild->stageinstances->push($stageinstance);
            $this->discord->guilds->push($guild);
        }

        $deferred->resolve($stageinstance);
    }
}
