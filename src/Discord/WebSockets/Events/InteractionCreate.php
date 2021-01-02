<?php

namespace Discord\WebSockets\Events;

use Discord\Helpers\Deferred;
use Discord\WebSockets\Event;

class InteractionCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        // do nothing with interactions - pass on to DiscordPHP-Slash
        $deferred->resolve($data);
    }
}
