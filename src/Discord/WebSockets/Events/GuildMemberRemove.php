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
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-member-remove
 *
 * @since 2.1.3
 */
class GuildMemberRemove extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $memberPart = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Member */
            $memberPart = yield $guild->members->cachePull($data->user->id);
            --$guild->member_count;
        }

        if ($memberPart) {
            $memberPart->created = false;
        } else {
            /** @var Member */
            $memberPart = $this->factory->part(Member::class, (array) $data);
        }

        $this->cacheUser($data->user);

        return $memberPart;
    }
}
