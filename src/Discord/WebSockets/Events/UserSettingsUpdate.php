<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\WebSockets\Event;
use React\Promise\Deferred;

/**
 * Event that is emitted when `USER_SETTINGS_UPDATE` is fired.
 */
class UserSettingsUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, array $data)
    {
        $this->discord->user_settings = (object) array_merge((array) $this->discord->user_settings, (array) $data);

        $deferred->resolve($data);
    }
}
