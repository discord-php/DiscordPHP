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

use Discord\Parts\WebSockets\RateLimited as RateLimitedPart;
use Discord\WebSockets\Event;

/**
 * A gateway request was rate limited. The client retries its own member requests by itself; this is for
 * requests you send, such as {@see \Discord\Discord::requestGuildMembers()}.
 *
 * @link https://docs.discord.com/developers/events/gateway-events#rate-limited
 *
 * @since 10.60.0
 */
class RateLimited extends Event
{
    /**
     * @inheritDoc
     */
    public function handle($data)
    {
        return $this->factory->part(RateLimitedPart::class, (array) $data, true);
    }
}
