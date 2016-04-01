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
 * Event that is emitted when `VOICE_STATE_UPDATE` is fired.
 */
class VoiceStateUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, array $data)
    {
        $data = json_decode(json_encode($data)); // this is the worst i am so sorry but it's late

        foreach ($this->discord->guilds as $index => $guild) {
            if ($guild->id == $data->guild_id) {
                $member = array_key_exists($data->user_id, $guild->members) ? $guild->members[$data->user_id] : null;
                if (is_null($member)) {
                    break;
                }

                $member->deaf = $data->deaf;
                $member->mute = $data->mute;

                $guild->members[$data->user_id] = $member;

                break;
            }
        }

        $deferred->resolve($data);
    }
}
