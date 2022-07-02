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

use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\WebSockets\AutoModerationActionExecution as ActionExecution;

/**
 * @see https://discord.com/developers/docs/topics/gateway#auto-moderation-action-execution
 */
class AutoModerationActionExecution extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var ActionExecution */
        $actionExecutionPart = $this->factory->create(ActionExecution::class, $data, true);

        $deferred->resolve($actionExecutionPart);
    }
}
