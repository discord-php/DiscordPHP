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
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-member-update
 *
 * @since 2.1.3
 */
class GuildMemberUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $memberPart = $oldMember = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Member */
            if ($oldMember = yield $guild->members->cacheGet($data->user->id)) {
                // Swap
                $memberPart = $oldMember;
                $oldMember = clone $oldMember;

                $memberPart->fill((array) $data);
            }
        }

        if ($memberPart === null) {
            /** @var Member */
            $memberPart = $this->factory->part(Member::class, (array) $data, true);
        }

        if (isset($guild)) {
            $guild->members->set($data->user->id, $memberPart);
        }

        $this->cacheUser($data->user);

        return [$memberPart, $oldMember];
    }
}
