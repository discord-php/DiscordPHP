<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Helpers;

use Evenement\EventEmitterTrait;

/**
 * Wraps try/catch around emit.
 */
trait EventEmitterTraitDebug
{
    use EventEmitterTrait {
        EventEmitterTrait::emit as parentEmit;
    }

    public function emit($event, array $arguments = []) {
        try
        {
            $this->parentEmit($event, $arguments);
        } catch (\Throwable $e) {
            $this->parentEmit('exception', [$e, $this]);
            $this->logger->error('exception caught in callback', ['event' => $event, 'type' => get_class($e), 'message' => $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine()]);
        }
    }
}
