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
 * @see https://discord.com/developers/docs/topics/gateway#guild-member-update
 */
class GuildMemberUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $memberPart = $oldMember = null;

        if ($guild = $this->discord->guilds->get('id', $data->guild_id)) {
            if ($oldMember = $guild->members->get('id', $data->user->id)) {
                // Swap
                $memberPart = $oldMember;
                $oldMember = clone $oldMember;

                $memberPart->fill((array) $data);
            }
        }

        if (! $memberPart) {
            /** @var Member */
            $memberPart = $this->factory->create(Member::class, $data, true);
            if ($guild = $memberPart->guild) {
                $guild->members->pushItem($memberPart);
            }
        }

        $this->cacheUser($data->user);

        $deferred->resolve([$memberPart, $oldMember]);
    }
}
