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
 * @see https://discord.com/developers/docs/topics/gateway#guild-member-remove
 */
class GuildMemberRemove extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $memberPart = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            $memberPart = $guild->members->pull($data->user->id);
            --$guild->member_count;
        }

        if ($memberPart) {
            $memberPart->created = false;
        } else {
            /** @var Member */
            $memberPart = $this->factory->create(Member::class, $data);
            $memberPart->guild_id = $data->guild_id;
        }

        $this->cacheUser($data->user);

        $deferred->resolve($memberPart);
    }
}
