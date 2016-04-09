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
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
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

        $channels = new ChannelRepository(
            $this->http,
            $this->cache,
            $this->partFactory,
            ['guild_id' => $guildPart->id]
        );

        foreach ($data->channels as $channel) {
            $channel             = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart         = $this->partFactory->create(Channel::class, $channel, true);

            $this->cache->set("channel.{$channelPart->id}", $channelPart);

            $channels->push($channelPart);
        }

        $guildPart->channels = $channels;
        unset($channels);

        // guild members
        $members = new MemberRepository(
            $this->http,
            $this->cache,
            $this->partFactory,
            ['guild_id' => $guildPart->id]
        );

        foreach ($data->members as $member) {
            $member             = (array) $member;
            $member['guild_id'] = $guildPart->id;
            $member['status']   = 'offline';
            $member['game']     = null;
            $memberPart         = $this->partFactory->create(Member::class, $member, true);

            // check for presences
            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member['user']->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game   = $presence->game;
                }
            }

            // Since when we use GUILD_MEMBERS_CHUNK, we have to cycle through the current members
            // and see if they exist already. That takes ~34ms per member, way way too much.
            $members[$memberPart->id] = $memberPart;
        }

        $guildPart->members = $members;
        unset($members);

        $roles = new RoleRepository(
            $this->http,
            $this->cache,
            $this->partFactory,
            ['guild_id' => $guildPart->id]
        );

        foreach ($data->roles as $role) {
            $role                = (array) $role;
            $role['guild_id']    = $guildPart->id;
            $rolePart            = $this->partFactory->create(Role::class, $role, true);

            $roles->push($rolePart);

            $this->cache->set("roles.{$rolePart->id}", $rolePart);
        }

        $guildPart->roles = $roles;
        unset($roles);

        $this->cache->set("guild.{$data->id}", $guildPart);
        $this->discord->guilds->push($guildPart);

        $deferred->resolve($guildPart);
    }
}
