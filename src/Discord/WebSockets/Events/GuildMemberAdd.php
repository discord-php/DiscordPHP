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

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-member-add
 */
class GuildMemberAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var Member */
        $memberPart = $this->factory->create(Member::class, $data, true);

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $guild->members->pushItem($memberPart);
            ++$guild->member_count;
        }

        $this->cacheUser($data->user);

        $deferred->resolve($memberPart);
    }
}
