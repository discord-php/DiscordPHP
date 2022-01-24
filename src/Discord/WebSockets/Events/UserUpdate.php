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
use Discord\Helpers\Deferred;
use Discord\Parts\User\User;

class UserUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        /** @var User */
        if ($olduser = $this->discord->users->offsetGet($data->id)) {
            $user = clone $olduser;
            $olduser->fill((array) $data);
        } else {
            $user = $this->factory->part(User::class, (array) $data, true);
        }

        $deferred->resolve([$user, $olduser]);
    }
}
