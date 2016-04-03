<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `GUILD_MEMBER_REMOVE` is fired.
 */
class GuildMemberRemove extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $data = $this->partFactory->create(Member::class, $data, true);

        $this->cache->remove("guild.{$data->guild_id}.members.{$data->id}");

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $guild->members->pull($data->user->id);
                $guild->member_count--;

                break;
            }
        }

        $deferred->resolve($data);
    }
}
