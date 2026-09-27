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

use Discord\WebSockets\Event;
use Discord\WebSockets\Events\Data\ChannelPinsUpdateData;

/**
 * @link https://docs.discord.com/developers/events/gateway-events#channel-pins-update
 *
 * @since 10.47.2 Returns a `ChannelPinsUpdateData` part instead of a generic object.
 * @since 4.0.4
 */
class ChannelPinsUpdate extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        return $this->factory->part(ChannelPinsUpdateData::class, (array) $data, true);
    }
}
