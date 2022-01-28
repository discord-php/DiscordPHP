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

use Discord\Parts\WebSockets\TypingStart as TypingStartPart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\User\User;

class TypingStart extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $typing = $this->factory->create(TypingStartPart::class, $data, true);

        // User caching
        if (isset($data->member->user)) {
            if ($user = $this->discord->users->get('id', $data->member->user->id)) {
                $user->fill((array) $data->member->user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->member->user, true));
            }
        }

        $deferred->resolve($typing);
    }
}
