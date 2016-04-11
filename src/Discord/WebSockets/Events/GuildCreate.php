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

use Discord\Cache\Cache;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Event;

/**
 * Event that is emitted wheh `GUILD_CREATE` is fired.
 */
class GuildCreate extends Event
{
    /**
     * {@inheritdoc}
     *
     * @return Guild The parsed data.
     */
    public function getData($data, $discord)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $this->emit('unavailable', [$data->id]);
        }

        $guildPart = new Guild((array) $data, true);

        $channels = new Collection();

        foreach ($data->channels as $channel) {
            $channel             = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart         = new Channel($channel, true);

            Cache::set("channel.{$channelPart->id}", $channelPart);

            $channels->push($channelPart);
        }

        $guildPart->setCache('channels', $channels);

        // guild members
        $members = new Collection();

        foreach ($data->members as $member) {
            $memberPart = new Member([
                'user'      => $member->user,
                'roles'     => $member->roles,
                'mute'      => $member->mute,
                'deaf'      => $member->deaf,
                'joined_at' => $member->joined_at,
                'guild_id'  => $data->id,
                'status'    => 'offline',
                'game'      => null,
            ], true);

            // check for presences

            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game   = $presence->game;
                }
            }

            Cache::set("guild.{$guildPart->id}.members.{$memberPart->id}", $memberPart);

            $members[$memberPart->id] = $memberPart;
        }

        $guildPart->setCache('members', $members);

        foreach ($data->voice_states as $state) {
            if ($channel = $guildPart->channels->get('id', $state->channel_id)) {
                $channel->members[$state->user_id] = new VoiceStateUpdate((array) $state, true);
            }
        }

        if ($guildPart->large) {
            $this->emit('large', [$guildPart]);
        }

        return $guildPart;
    }

    /**
     * {@inheritdoc}
     */
    public function updateDiscordInstance($data, $discord)
    {
        Cache::set("guild.{$data->id}", $data);

        $discord->guilds->push($data);

        return $discord;
    }
}
