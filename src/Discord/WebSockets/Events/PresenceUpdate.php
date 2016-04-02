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
use Discord\Parts\WebSockets\PresenceUpdate as PresenceUpdatePart;

/**
 * Event that is emitted wheh `PRESENCE_UPDATE` is fired.
 */
class PresenceUpdate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return PresenceUpdatePart The parsed data.
     */
    public function getData($data, $discord)
    {
        return new PresenceUpdatePart((array) $data, true);
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        foreach ($discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $member = @$guild->members[$data->user->id];

                if (! is_null($member)) {
                    $member->game   = $data->game;
                    $member->status = $data->status;

                    $guild->members[$data->user->id] = $member;
                }

                break;
            }
        }

        return $discord;
    }
}
