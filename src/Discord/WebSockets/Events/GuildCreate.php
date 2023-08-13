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

use Discord\Helpers\Deferred;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Http\Endpoint;

use function React\Promise\all;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#guild-create
 *
 * @since 2.1.3
 */
class GuildCreate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        if (! empty($data->unavailable)) {
            return $data;
        }

        /** @var Guild */
        $guildPart = $this->factory->part(Guild::class, (array) $data, true);

        foreach ($data->members as $member) {
            $userId = $member->user->id;
            $member->guild_id = $data->id;
            $rawMembers[$userId] = $member;
            /** @var Member[] */
            $members[$userId] = $this->factory->part(Member::class, (array) $rawMembers[$userId], true);

            if (! $this->discord->users->offsetExists($userId)) {
                $await[] = $this->discord->users->cache->set($userId, $this->discord->users->create($member->user, true));
            }
        }
        foreach ($data->presences as $presence) {
            if (! array_key_exists($presence->user->id, $members)) {
                $this->discord->getLogger()->warning('Presence data exists but no member data was received', [
                    'user_id' => $presence->user->id,
                    'guild_id' => $data->id,
                    'large' => $data->large,
                    'member_count' => $data->member_count,
                    'members_count' => count($data->members),
                    'rawMembers_looped' => count($rawMembers),
                    'presences_count' => count($data->presences),
                ]);
                continue;
            }
            $members[$presence->user->id]->fill((array) $presence);
        }
        if (! empty($members)) {
            $await[] = $guildPart->members->cache->setMultiple($members);
        }

        foreach ($data->voice_states as $voice_state) {
            /** @var ?Channel */
            if ($voiceChannel = $guildPart->channels->offsetGet($voice_state->channel_id)) {
                $userId = $voice_state->user_id;
                $voice_state->guild_id = $data->id;
                if (! isset($voice_state->member) && isset($rawMembers[$userId])) {
                    $voice_state->member = $rawMembers[$userId];
                }
                $await[] = $voiceChannel->members->cache->set($userId, $voiceChannel->members->create($voice_state, true));
            }
        }

        foreach ($data->threads as $thread) {
            /** @var ?Channel */
            if ($parent = $guildPart->channels->offsetGet($thread->parent_id)) {
                $await[] = $parent->threads->cache->set($thread->id, $parent->threads->create($thread, true));
            }
        }

        foreach ($data->stage_instances as $stageInstance) {
            /** @var ?Channel */
            if ($channel = $guildPart->channels->offsetGet($stageInstance->channel_id)) {
                $await[] = $channel->stage_instances->cache->set($stageInstance->id, $channel->stage_instances->create($stageInstance, true));
            }
        }

        foreach ($data->guild_scheduled_events as $scheduledEvent) {
            $await[] = $guildPart->guild_scheduled_events->cache->set($scheduledEvent->id, $guildPart->guild_scheduled_events->create($scheduledEvent, true));
        }

        $all = yield all($await)->then(function () use (&$guildPart) {
            return $this->discord->guilds->cache->set($guildPart->id, $guildPart)->then(function ($success) use ($guildPart) {
                return $guildPart;
            });
        });

        if (
            (
                $this->discord->options['retrieveBans'] === true
                || (
                    is_array($this->discord->options['retrieveBans'])
                    && in_array($data->id, $this->discord->options['retrieveBans'])
                )
            )
            && $members[$this->discord->id]->getPermissions()->ban_members ?? true
        ) {
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
                        $guildPart->bans->cache->set($lastUserId, $guildPart->bans->create($ban, true));
                    }

                    $banPagination($lastUserId);
                }, [$loadBans, 'resolve']);
            };
            $banPagination();
            yield $loadBans->promise();
        }

        if ($data->large || $data->member_count > count($rawMembers)) {
            $this->discord->addLargeGuild($guildPart);
        }

        return $all;
    }
}
