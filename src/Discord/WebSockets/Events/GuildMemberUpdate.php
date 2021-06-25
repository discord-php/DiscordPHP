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

use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class GuildMemberUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var \Discord\Parts\User\Member */
        $memberPart = $this->factory->create(Member::class, $data, true);
        $old = null;

        if ($guild = $this->discord->guilds->get('id', $memberPart->guild_id)) {
            $old = $guild->members->get('id', $memberPart->id);
            $raw = (is_null($old)) ? [] : $old->getRawAttributes();
            $memberPart = $this->factory->create(Member::class, array_merge($raw, (array) $data), true);

            $guild->members->push($memberPart);
        }

        if ($user = $this->discord->users->get('id', $data->user->id)) {
            $user->fill((array) $data->user);
        }

        $deferred->resolve([$memberPart, $old]);
    }
}
