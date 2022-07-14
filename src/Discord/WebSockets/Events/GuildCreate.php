<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
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
use Discord\Http\Endpoint;
use Discord\Parts\Channel\StageInstance;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Thread\Member as ThreadMember;
use Discord\Parts\Thread\Thread;

/**
 * @see https://discord.com/developers/docs/topics/gateway#guild-create
 */
class GuildCreate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data)
    {
        if (isset($data->unavailable) && $data->unavailable) {
            $deferred->reject(['unavailable', $data->id]);

            return $deferred->promise();
        }

        /** @var Guild */
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
                $state = (array) $state;
                $state['guild_id'] = $guildPart->id;

                $stateUpdate = $this->factory->create(VoiceStateUpdatePart::class, $state, true);

                $channel->members->offsetSet($stateUpdate->user_id, $stateUpdate);
                $guildPart->channels->offsetSet($channel->id, $channel);
            }
        }

        foreach ($data->threads as $rawThread) {
            /**
             * @var Thread
             */
            $thread = $this->factory->create(Thread::class, $rawThread, true);

            if ($rawThread->member ?? null) {
                $member = (array) $rawThread->member;
                $member['id'] = $thread->id;
                $member['user_id'] = $this->discord->id;

                $selfMember = $this->factory->create(ThreadMember::class, $member, true);
                $thread->members->pushItem($selfMember);
            }

            $guildPart->channels->get('id', $thread->parent_id)->threads->pushItem($thread);
        }

        foreach ($data->stage_instances as $stageInstance) {
            $stageInstance->guild_id = $data->id;
            /** @var StageInstance */
            $stageInstancePart = $this->factory->create(StageInstance::class, $stageInstance, true);

            $guildPart->stage_instances->offsetSet($stageInstancePart->id, $stageInstancePart);
        }

        foreach ($data->guild_scheduled_events as $scheduledEvent) {
            $scheduledEvent->guild_id = $data->id;
            /** @var ScheduledEvent */
            $scheduledEventPart = $this->factory->create(ScheduledEvent::class, $scheduledEvent, true);

            $guildPart->guild_scheduled_events->offsetSet($scheduledEventPart->id, $scheduledEventPart);
        }

        $resolve = function () use (&$guildPart, $deferred) {
            if ($guildPart->large || $guildPart->member_count > $guildPart->members->count()) {
                $this->discord->addLargeGuild($guildPart);
            }

            $this->discord->guilds->offsetSet($guildPart->id, $guildPart);

            $deferred->resolve($guildPart);
        };

        if ($this->discord->options['retrieveBans']) {
            $banPagination = function ($lastUserId = null) use (&$banPagination, $guildPart, $resolve) {
                $bind = Endpoint::bind(Endpoint::GUILD_BANS, $guildPart->id);
                if (isset($lastUserId)) {
                    $bind->addQuery('after', $lastUserId);
                }
                $this->http->get($bind)->done(function ($rawBans) use (&$banPagination, $guildPart, $resolve) {
                    if (empty($rawBans)) {
                        $resolve();

                        return;
                    }

                    foreach ($rawBans as $ban) {
                        $ban = (array) $ban;
                        $ban['guild_id'] = $guildPart->id;

                        $banPart = $this->factory->create(Ban::class, $ban, true);

                        $guildPart->bans->offsetSet($banPart->user->id, $banPart);
                    }

                    $banPagination($guildPart->bans->last()->user->id);
                }, $resolve);
            };
            $banPagination();
        } else {
            $resolve();
        }
    }
}
