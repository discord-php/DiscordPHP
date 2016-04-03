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

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Illuminate\Support\Collection;
use React\Promise\Deferred;

/**
 * Event that is emitted when `GUILD_CREATE` is fired.
 */
class GuildCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $guildPart = $this->partFactory->create(Guild::class, $data, true);

        $channels = new Collection();

        foreach ($data->channels as $channel) {
            $channel             = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart         = $this->partFactory->create(Channel::class, $channel, true);

            $this->cache->set("channel.{$channelPart->id}", $channelPart);

            $channels->push($channelPart);
        }

        $guildPart->setCache('channels', $channels);

        // guild members
        $members = new Collection();

        foreach ($data->members as $member) {
            $memberPart = $this->partFactory->create(
                Member::class,
                [
                    'user'      => $member->user,
                    'roles'     => $member->roles,
                    'mute'      => $member->mute,
                    'deaf'      => $member->deaf,
                    'joined_at' => $member->joined_at,
                    'guild_id'  => $data->id,
                    'status'    => 'offline',
                    'game'      => null,
                ],
                true
            );

            // check for presences

            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game   = $presence->game;
                }
            }

            $this->cache->set("guild.{$guildPart->id}.members.{$memberPart->id}", $memberPart);

            $members->push($memberPart);
        }

        $guildPart->setCache('members', $members);
        $this->cache->set("guild.{$data->id}", $guildPart);
        $this->discord->guilds->push($guildPart);

        $deferred->resolve($guildPart);
    }
}
