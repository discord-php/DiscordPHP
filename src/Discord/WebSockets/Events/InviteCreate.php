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

use Discord\Parts\Guild\Invite;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#invite-create
 */
class InviteCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $invite = $this->factory->create(Invite::class, $data, true);

        if (isset($data->inviter)) {
            // User caching from inviter
            $this->cacheUser($data->inviter);
        }

        if (isset($data->target_user)) {
            // User caching from target user
            $this->cacheUser($data->target_user);
        }

        $deferred->resolve($invite);
    }
}
