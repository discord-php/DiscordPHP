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

use Discord\WebSockets\Event;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#invite-create
 *
 * @since 5.0.0
 */
class InviteCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        /** @var Invite */
        $invitePart = $this->factory->part(Invite::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            if ($channel = yield $guild->channels->cacheGet($data->channel_id)) {
                $channel->invites->set($data->code, $invitePart);
            }

            $guild->invites->set($data->code, $invitePart);
        }

        if (isset($data->inviter)) {
            // User caching from inviter
            $this->cacheUser($data->inviter);
        }

        if (isset($data->target_user)) {
            // User caching from target user
            $this->cacheUser($data->target_user);
        }

        return $invitePart;
    }
}
