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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Thread\Member;
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#thread-member-update
 *
 * @since 7.0.0
 */
class ThreadMemberUpdate extends Event
{
    public function handle($data)
    {
        /** @var Member */
        $memberPart = $this->factory->part(Member::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var Channel */
            foreach ($guild->channels as $channel) {
                /** @var ?Thread */
                if ($thread = yield $channel->threads->cacheGet($data->id)) {
                    $thread->members->set($data->user_id, $memberPart);
                    break;
                }
            }
        }

        return $memberPart;
    }
}
