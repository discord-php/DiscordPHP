<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
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
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class GuildCreate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->reject(['unavailable', $data->id]);

            return $deferred->promise();
        }

        $guildPart = $this->factory->create(Guild::class, $data, true);
        foreach ($data->roles as $role) {
            $role = (array) $role;
            $role['guild_id'] = $guildPart->id;
            $rolePart = $this->factory->create(Role::class, $role, true);

            $guildPart->roles->offsetSet($rolePart->id, $rolePart);
        }

        foreach ($data->channels as $channel) {
            $channel = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart = $this->factory->create(Channel::class, $channel, true);

            $guildPart->channels->offsetSet($channelPart->id, $channelPart);
        }

        foreach ($data->members as $member) {
            $member = (array) $member;
            $member['guild_id'] = $data->id;

            if (! $this->discord->users->has($member['user']->id)) {
                $userPart = $this->factory->create(User::class, $member['user'], true);
                $this->discord->users->offsetSet($userPart->id, $userPart);
            }

            $memberPart = $this->factory->create(Member::class, $member, true);
            $guildPart->members->offsetSet($memberPart->id, $memberPart);
        }

        foreach ($data->presences as $presence) {
            if ($member = $guildPart->members->offsetGet($presence->user->id)) {
                $member->fill((array) $presence);
                $guildPart->members->offsetSet($member->id, $member);
            }
        }

        foreach ($data->voice_states as $state) {
            if ($channel = $guildPart->channels->offsetGet($state->channel_id)) {
                $stateUpdate = $this->factory->create(VoiceStateUpdatePart::class, $state, true);

                $channel->members->offsetSet($stateUpdate->user_id, $stateUpdate);
                $guildPart->channels->offsetSet($channel->id, $channel);
            }
        }

        $resolve = function () use (&$guildPart, $deferred) {
            if ($guildPart->large) {
                $this->discord->addLargeGuild($guildPart);
            }

            $this->discord->guilds->offsetSet($guildPart->id, $guildPart);

            $deferred->resolve($guildPart);
        };

        if ($this->discord->options['retrieveBans']) {
            $this->http->get("guilds/{$guildPart->id}/bans")->done(function ($rawBans) use (&$guildPart, $resolve) {
                foreach ($rawBans as $ban) {
                    $ban = (array) $ban;
                    $ban['guild'] = $guildPart;

                    $banPart = $this->factory->create(Ban::class, $ban, true);

                    $guildPart->bans->offsetSet($banPart->id, $banPart);
                }

                $resolve();
            }, $resolve);
        } else {
            $resolve();
        }
    }
}
