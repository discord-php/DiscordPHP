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
use Discord\Parts\WebSockets\AutoModerationActionExecution as ActionExecution;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#auto-moderation-action-execution
 *
 * @see \Discord\Parts\WebSockets\AutoModerationActionExecution
 *
 * @since 7.1.0
 */
class AutoModerationActionExecution extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var ActionExecution */
        $actionExecutionPart = $this->factory->part(ActionExecution::class, (array) $data, true);

        return $actionExecutionPart;
    }
}
