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
use Discord\Parts\User\Member;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Http\Endpoint;
use Discord\Parts\Channel\StageInstance;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Thread\Thread;

use function React\Promise\all;

/**
 * @link https://discord.com/developers/docs/topics/gateway#guild-create
 *
 * @since 2.1.3
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

        foreach ($data->channels as $channel) {
            /** @var Channel[] */
            $channel->guild_id = $data->id;
            $channels[$channel->id] = $this->factory->create(Channel::class, $channel, true);
        }
        if (! empty($channels)) {
            $await[] = $guildPart->channels->cache->setMultiple($channels);
        }

        foreach ($data->members as $member) {
            $userId = $member->user->id;
            $member->guild_id = $data->id;
            $rawMembers[$userId] = $member;
            /** @var Member[] */
            $members[$userId] = $this->factory->create(Member::class, $rawMembers[$userId], true);

            if (! $this->discord->users->offsetExists($userId)) {
                /** @var User[] */
                $users[$userId] = $this->factory->create(User::class, $member->user, true);
            }
        }
        if (! empty($users)) {
            $await[] = $this->discord->users->cache->setMultiple($users);
        }
        foreach ($data->presences as $presence) {
            $members[$presence->user->id]->fill((array) $presence);
        }
        if (! empty($members)) {
            $await[] = $guildPart->members->cache->setMultiple($members);
        }

        foreach ($data->voice_states as $voice_state) {
            if (isset($channels[$voice_state->channel_id])) {
                $channelId = $voice_state->channel_id;
                $userId = $voice_state->user_id;
                $voice_state->guild_id = $data->id;
                if (! isset($voice_state['member']) && isset($rawMembers[$userId])) {
                    $voice_state['member'] = $rawMembers[$userId];
                }
                $await[] = $channels[$channelId]->members->cache->set($userId, $this->factory->create(VoiceStateUpdatePart::class, $voice_state, true));
            }
        }

        foreach ($data->threads as $thread) {
            if (isset($channels[$thread->parent_id])) {
                $await[] = $channels[$thread->parent_id]->threads->cache->set($thread->id, $this->factory->create(Thread::class, $thread, true));
            }
        }

        foreach ($data->stage_instances as $stageInstance) {
            /** @var StageInstance[] */
            $stageInstances[$stageInstance->id] = $this->factory->create(StageInstance::class, $stageInstance, true);
        }
        if (! empty($stageInstances)) {
            $await[] = $guildPart->stage_instances->cache->setMultiple($stageInstances);
        }

        foreach ($data->guild_scheduled_events as $scheduledEvent) {
            /** @var ScheduledEvent[] */
            $scheduledEvents[$scheduledEvent->id] = $this->factory->create(ScheduledEvent::class, $scheduledEvent, true);
        }
        if (! empty($scheduledEvents)) {
            $await[] = $guildPart->guild_scheduled_events->cache->setMultiple($scheduledEvents);
        }

        if ($this->discord->options['retrieveBans']) {
            $canBan = true; // Assume so since the permission might fail to be determined
            if ($botPerms = $members[$this->discord->id]->getPermissions()) {
                $canBan = $botPerms->ban_members;
            }

            if ($canBan) {
                $loadBans = new Deferred();
                $banPagination = function ($lastUserId = null) use (&$banPagination, $guildPart, $loadBans) {
                    $bind = Endpoint::bind(Endpoint::GUILD_BANS, $guildPart->id);
                    if (isset($lastUserId)) {
                        $bind->addQuery('after', $lastUserId);
                    }
                    $this->http->get($bind)->done(function ($bans) use (&$banPagination, $guildPart, $loadBans) {
                        if (empty($bans)) {
                            $loadBans->resolve();

                            return;
                        }

                        foreach ($bans as $ban) {
                            $lastUserId = $ban->user->id;
                            $ban->guild_id = $guildPart->id;
                            $guildPart->bans->cache->set($lastUserId, $this->factory->create(Ban::class, $ban, true));
                        }

                        $banPagination($lastUserId);
                    }, [$loadBans, 'resolve']);
                };
                $banPagination();
                $await[] = $loadBans->promise();
            }
        }

        all($await)->then(function () use (&$guildPart) {
            return $this->discord->guilds->cache->set($guildPart->id, $guildPart)->then(function ($success) use ($guildPart) {
                return $guildPart;
            });
        })->then([$deferred, 'resolve']);

        if ($data->large || $data->member_count > count($rawMembers)) {
            $this->discord->addLargeGuild($guildPart);
        }
    }
}
