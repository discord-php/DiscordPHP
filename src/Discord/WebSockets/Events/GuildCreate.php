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

use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;

class GuildCreate extends Event
{
    /**
     * Returns the formatted data.
     *
     * @param array   $data
     * @param Discord $discord
     *
     * @return Message
     */
    public function getData($data, $discord)
    {
        $guildPart = new Guild((array) $data, true);

        $channels = new Collection();

        foreach ($data->channels as $channel) {
            $channel = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart = new Channel($channel, true);

            $channels->push($channelPart);
        }

        $guildPart->setCache('channels', $channels);

        // guild members
        $members = new Collection();

        foreach ($data->members as $member) {
            $memberPart = new Member([
                'user' => $member->user,
                'roles' => $member->roles,
                'mute' => $member->mute,
                'deaf' => $member->deaf,
                'joined_at' => $member->joined_at,
                'guild_id' => $data->id,
                'status' => 'offline',
                'game' => null,
            ], true);

            // check for presences

            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game = $presence->game;
                }
            }

            $members->push($memberPart);
        }

        $guildPart->setCache('members', $members);

        return $guildPart;
    }

    /**
     * Updates the Discord instance with the new data.
     *
     * @param mixed   $data
     * @param Discord $discord
     *
     * @return Discord
     */
    public function updateDiscordInstance($data, $discord)
    {
        $discord->guilds->push($data);

        return $discord;
    }
}
