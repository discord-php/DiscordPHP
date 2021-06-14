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
use Discord\Parts\User\Member as UserMember;
use Discord\Parts\WebSockets\PresenceUpdate;
use Discord\WebSockets\Event;

class ThreadMembersUpdate extends Event
{
    public function handle(Deferred &$deferred, $data)
    {
        $guild = $this->discord->guilds->get('id', $data->guild_id);

        if ($thread = $guild->threads->get('id', $data->id)) {
            $thread->member_count = $data->member_count;

            if ($data->removed_member_ids ?? null) {
                foreach ($data->removed_member_ids as $id) {
                    $thread->members->pull($id);
                }
            }

            if ($data->added_members ?? null) {
                foreach ($data->added_members as $member) {
                    $member = $this->factory->create(Member::class, $member, true);
                    $thread->members->push($member);
                }
            }
        }

        $deferred->resolve($thread);
    }
}
