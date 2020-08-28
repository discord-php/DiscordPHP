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
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
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
        foreach ($data->roles as $role) {
            $role = (array) $role;
            $role['guild_id'] = $guildPart->id;
            $rolePart = $this->factory->create(Role::class, $role, true);

            $guildPart->roles->push($rolePart);
        }

        foreach ($data->channels as $channel) {
            $channel = (array) $channel;
            $channel['guild_id'] = $data->id;
            $channelPart = $this->factory->create(Channel::class, $channel, true);

            $guildPart->channels->push($channelPart);
        }

        if ($this->discord->options['loadAllMembers']) {
            foreach ($data->members as $member) {
                $member = (array) $member;
                $member['guild_id'] = $data->id;
                $memberPart = $this->factory->create(Member::class, $member, true);

                $this->discord->users->push($memberPart->user);
                $guildPart->members->push($memberPart);
            }

            foreach ($data->presences as $presence) {
                if ($member = $guildPart->members->get('id', $presence->user->id)) {
                    $member->fill($presence);
                    $guildPart->members->push($member);
                }
            }
        }

        foreach ($data->voice_states as $state) {
            if ($channel = $guildPart->channels->get('id', $state->channel_id)) {
                $channel->members->push($this->factory->create(VoiceStateUpdatePart::class, (array) $state, true));
                $guildPart->channels->push($channel);
            }
        }

        $resolve = function () use (&$guildPart, $deferred) {
            if ($guildPart->large) {
                $this->discord->addLargeGuild($guildPart);
            }

            $this->discord->guilds->push($guildPart);

            $deferred->resolve($guildPart);
        };

        if ($this->discord->options['retrieveBans']) {
            $this->http->get("guilds/{$guildPart->id}/bans")->then(function ($rawBans) use (&$guildPart, $resolve) {
                foreach ($rawBans as $ban) {
                    $ban = (array) $ban;
                    $ban['guild'] = $guildPart;

                    $banPart = $this->factory->create(Ban::class, $ban, true);

                    $guildPart->bans->push($banPart);
                }

                $resolve();
            }, $resolve);
        } else {
            $resolve();
        }
    }
}
