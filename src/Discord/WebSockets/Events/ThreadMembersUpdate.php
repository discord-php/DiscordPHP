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

use Discord\Helpers\Deferred;
use Discord\Parts\Thread\Member;
use Discord\WebSockets\Event;

/**
 * @see https://discord.com/developers/docs/topics/gateway#thread-members-update
 */
class ThreadMembersUpdate extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);

        // When the bot is added to a private thread, sometimes the `THREAD_MEMBER_UPDATE` event
        // comes before the `THREAD_CREATE` event, so we just don't emit this event if we don't have the
        // thread cached.
        foreach ($guild->channels as $channel) {
            if ($thread = $channel->threads->get('id', $data->id)) {
                $thread->member_count = $data->member_count;

                if ($data->removed_member_ids ?? null) {
                    foreach ($data->removed_member_ids as $id) {
                        $thread->members->pull($id);
                    }
                }

                if ($data->added_members ?? null) {
                    foreach ($data->added_members as $member) {
                        $member = $this->factory->create(Member::class, $member, true);
                        $thread->members->pushItem($member);
                    }
                }

                $deferred->resolve($thread);

                return;
            }
        }
    }
}
