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

/**
 * Event that is emitted wheh `USER_SETTINGS_UPDATE` is fired.
 */
class UserSettingsUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return array The data.
     */
    public function getData($data, $discord)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        $new = (object) array_merge((array) $discord->user_settings, (array) $data);

        $discord->user_settings = $new;

        return $discord;
    }
}
