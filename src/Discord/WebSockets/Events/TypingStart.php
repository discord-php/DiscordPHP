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

use Discord\Parts\Guild\Guild;
use Discord\Parts\WebSockets\TypingStart as TypingStartPart;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#typing-start
 *
 * @see \Discord\Parts\WebSockets\TypingStart
 *
 * @since 2.1.3
 */
class TypingStart extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $typing = $this->factory->part(TypingStartPart::class, (array) $data, true);

        if (isset($data->member)) {
            /** @var ?Guild */
            if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
                $this->cacheMember($guild->members, (array) $data->member);
            }

            $this->cacheUser($data->member->user);
        }

        return $typing;
    }
}
