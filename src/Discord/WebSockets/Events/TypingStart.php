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

use Discord\Parts\WebSockets\TypingStart as TypingStartPart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class TypingStart extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $typing = $this->factory->create(TypingStartPart::class, $data, true);

        $deferred->resolve($typing);
    }
}
