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
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildMemberUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $memberPart = $this->factory->create(Member::class, (array) $data, true);
        $old = null;

        if ($guild = $this->discord->guilds->get('id', $memberPart->guild_id)) {
            $old = $guild->members->get('id', $memberPart->id);
            $raw = (is_null($old)) ? [] : $old->getRawAttributes();
            $memberPart = $this->factory->create(Member::class, array_merge($raw, (array) $data), true);

            $guild->members->push($memberPart);

            $this->discord->guilds->push($guild);
        }

        $deferred->resolve([$memberPart, $old]);
    }
}
