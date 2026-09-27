<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\WebSockets\ChannelInfoChannel;
use Discord\WebSockets\Event;

/**
 * Sent when the voice channel start time changes.
 *
 * This can be used to sync up the client's voice connection if it becomes out of sync.
 *
 * @since 10.48.0
 */
class VoiceChannelStartTimeUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        return $this->factory->part(ChannelInfoChannel::class, (array) $data, true);
    }
}
