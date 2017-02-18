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
use Discord\Parts\Guild\Ban;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Role;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\Repository\Guild\BanRepository;
use Discord\Repository\Guild\ChannelRepository;
use Discord\Repository\Guild\MemberRepository;
use Discord\Repository\Guild\RoleRepository;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class GuildCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->reject(['unavailable', $data->id]);

            return $deferred->promise();
        }

        $guildPart = $this->factory->create(Guild::class, $data, true);

        $this->discord->guilds->offsetSet($guildPart->id, $guildPart);

        $roles = new RoleRepository(
            $this->http,
            $this->cache,
            $this->factory,
            $guildPart->getRepositoryAttributes()
        );

        foreach ($data->roles as $role) {
            $role             = (array) $role;
            $role['guild_id'] = $guildPart->id;
            $rolePart         = $this->factory->create(Role::class, $role, true);

            $roles->offsetSet($rolePart->id, $rolePart);
        }

        $channels = new ChannelRepository(
            $this->http,
            $this->cache,
            $this->factory,
            $guildPart->getRepositoryAttributes()
        );

        foreach ($data->channels as $channel) {
            $channel             = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart         = $this->factory->create(Channel::class, $channel, true);

            $channels->offsetSet($channelPart->id, $channelPart);
        }

        $members = new MemberRepository(
            $this->http,
            $this->cache,
            $this->factory,
            $guildPart->getRepositoryAttributes()
        );

        foreach ($data->members as $member) {
            $memberPart = $this->factory->create(Member::class, [
                'user'      => $member->user,
                'roles'     => $member->roles,
                'mute'      => $member->mute,
                'deaf'      => $member->deaf,
                'joined_at' => $member->joined_at,
                'nick'      => (property_exists($member, 'nick')) ? $member->nick : null,
                'guild_id'  => $data->id,
                'status'    => 'offline',
                'game'      => null,
            ], true);

            foreach ($data->presences as $presence) {
                if ($presence->user->id == $member->user->id) {
                    $memberPart->status = $presence->status;
                    $memberPart->game   = $presence->game;
                }
            }

            $this->discord->users->offsetSet($memberPart->id, $memberPart->user);
            $members->offsetSet($memberPart->id, $memberPart);
        }

        $guildPart->roles    = $roles;
        $guildPart->channels = $channels;
        $guildPart->members  = $members;

        foreach ($data->voice_states as $state) {
            if ($channel = $guildPart->channels->get('id', $state->channel_id)) {
                $channel->members->offsetSet($state->id, $this->factory->create(VoiceStateUpdatePart::class, (array) $state, true));
            }
        }

        $resolve = function () use (&$guildPart, $deferred) {
            if ($guildPart->member_count > 100) {
                $this->discord->addLargeGuild($guildPart);
            }

            $this->discord->guilds->offsetSet($guildPart->id, $guildPart);

            $deferred->resolve($guildPart);
        };

        if ($this->discord->options['retrieveBans']) {
            $this->http->get("guilds/{$guildPart->id}/bans")->then(function ($rawBans) use (&$guildPart, $resolve) {
                $bans = new BanRepository(
                    $this->http,
                    $this->cache,
                    $this->factory,
                    $guildPart->getRepositoryAttributes()
                );

                foreach ($rawBans as $ban) {
                    $ban = (array) $ban;
                    $ban['guild'] = $guildPart;

                    $banPart = $this->factory->create(Ban::class, $ban, true);

                    $bans->offsetSet($banPart->id, $banPart);
                }

                $guildPart->bans = $bans;
                $resolve();
            }, $resolve);
        } else {
            $resolve();
        }
    }
}
