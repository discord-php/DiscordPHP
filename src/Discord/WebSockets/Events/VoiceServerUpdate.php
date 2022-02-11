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

use Discord\Parts\WebSockets\VoiceServerUpdate as VoiceServerUpdatePart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#voice-server-update
 */
class VoiceServerUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $part = $this->factory->create(VoiceServerUpdatePart::class, $data, true);

        $deferred->resolve($part);
    }
}
