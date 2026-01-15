<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\WebSockets\Event;
use Discord\Http\Endpoint;
use Discord\Parts\WebSockets\VoiceStateUpdate;
use Discord\WebSockets\Events\Data\GuildCreateData;
use React\Promise\Deferred;

use function React\Promise\all;

/**
 * The inner payload is either a guild object or an unavailable guild object.
 *
 * This event can be sent in three different scenarios:
 * 1. When a user is initially connecting, to lazily load and backfill information for all unavailable guilds sent in the Ready event. Guilds that are unavailable due to an outage will send a Guild Delete event.
 * 2. When a Guild becomes available again to the client.
 * 3. When the current user joins a new Guild.
 * During an outage, the guild object in scenarios 1 and 3 may be marked as unavailable.
 *
 * If you don't have the `GUILD_PRESENCES` Gateway Intent, or if the guild is over 75k members, it will only send members who are in voice, plus the member for you (the connecting user).
 * Otherwise, if a guild has over `large_threshold` members (configurable in Gateway Identify), it will only send members who are online, have a role, have a nickname, or are in a voice channel.
 * Otherwise (if it has under `large_threshold` members), it will send all members.
 *
 * @link https://discord.com/developers/docs/events/gateway-events#guild-create
 *
 * @since 2.1.3
 */
class GuildCreate extends Event
{
    /**
     * @inheritDoc
     *
     * @param GuildCreateData $data
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
            $await[] = $guildPart->voice_states->cache->set($voice_state->user_id, $this->factory->part(VoiceStateUpdate::class, (array) $voice_state, true));
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

        foreach ($data->soundboard_sounds as $sound) {
            $await[] = $guildPart->sounds->cache->set($sound->sound_id, $guildPart->sounds->create($sound, true));
        }

        $all = yield all($await)->then(function () use (&$guildPart) {
            return $this->discord->guilds->cache->set($guildPart->id, $guildPart)->then(fn ($success) => $guildPart);
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
                $this->http->get($bind)->then(function ($bans) use (&$banPagination, $guildPart, $loadBans) {
                    if (empty($bans)) {
                        $loadBans->resolve(null);

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
