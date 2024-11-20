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
use Discord\Parts\Thread\Thread;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#thread-members-update
 *
 * @since 7.0.0
 */
class ThreadMembersUpdate extends Event
{
    public function handle($data)
    {
        /** @var ?Guild */
        if (! $guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            return null;
        }

        // When the bot is added to a private thread, sometimes the `THREAD_MEMBER_UPDATE` event
        // comes before the `THREAD_CREATE` event, so we just don't emit this event if we don't have the
        // thread cached.
        // @todo channels may be missing from cache
        /** @var Channel */
        foreach ($guild->channels as $channel) {
            /** @var ?Thread */
            if ($thread = yield $channel->threads->cacheGet($data->id)) {
                $thread->member_count = $data->member_count;

                if (isset($data->removed_member_ids)) {
                    yield $thread->members->cache->deleteMultiple($data->removed_member_ids);
                }

                foreach ($data->added_members ?? [] as $member) {
                    $thread->members->set($member->user_id, $thread->members->create((array) $member + ['guild_id' => $data->guild_id], true));

                    if (isset($member->member)) {
                        $this->cacheMember($guild->members, (array) $member->member);
                        $this->cacheUser($member->member->user);
                    }
                }

                return $thread;
            }
        }

        return null;
    }
}
