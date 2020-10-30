<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildMemberListUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            foreach($data->ops as $ops) {
                if ($ops->op == 'SYNC') {
                    foreach($ops->items as $item) {
                        foreach($item->member as $member) {
                            if (! $this->discord->users->has($member->id)) {
                                $userPart = $this->factory->create(User::class, $member->user, true);
                                $this->discord->users->push($userPart);
                            }

                            if (! $guild->members->has($member->id)) {
                                $memberPart = $this->factory->create(Member::class, $member, true);
                                $guild->members->push($memberPart);
                            }
                        }
                    }
                } elseif (in_array($ops->op, ['INSERT','UPDATE'])) {
                    if (isset($data->item) && isset($data->item->member)) {
                        if (! $this->discord->users->has($data->item->member->id)) {
                            $userPart = $this->factory->create(User::class, $member->user, true);
                                $this->discord->users->push($userPart);
                            }

                            if (! $guild->members->has($member->user->id)) {
                                $memberPart = $this->factory->create(Member::class, $member, true);
                                $guild->members->push($memberPart);
                            }
					}
                } elseif ($ops->op == 'INVALIDATE') {
					/* WIP */
                } elseif ($ops->op == 'DELETE') {
					/* WIP */
                }
            }

            $this->discord->guilds->push($guild);
        }

        $deferred->resolve($this->discord->guilds->get('id', $data->guild_id));
    }
}
