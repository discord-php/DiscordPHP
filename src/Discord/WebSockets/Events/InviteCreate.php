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
use Discord\Parts\User\User;

class InviteCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $invite = $this->factory->create(Invite::class, $data, true);

        // User caching from inviter
        if (isset($data->inviter)) {
            if ($user = $this->discord->users->get('id', $data->inviter->id)) {
                $user->fill((array) $data->inviter);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->inviter, true));
            }
        }

        // User caching from target user
        if (isset($data->target_user)) {
            if ($user = $this->discord->users->get('id', $data->target_user->id)) {
                $user->fill((array) $data->target_user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->target_user, true));
            }
        }

        $deferred->resolve($invite);
    }
}
