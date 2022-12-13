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

use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#voice-state-update
 *
 * @see \Discord\Parts\WebSockets\VoiceStateUpdate
 *
 * @since 2.1.3
 */
class VoiceStateUpdate extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        $oldVoiceState = null;
        /** @var VoiceStateUpdatePart */
        $statePart = $this->factory->part(VoiceStateUpdatePart::class, (array) $data, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            // Preload target new voice state channel
            yield $guild->channels->cacheGet($data->channel_id);

            /** @var ?Channel */
            foreach ($guild->channels as $channel) {
                if (! $channel->isVoiceBased()) {
                    continue;
                }

                /** @var ?VoiceStateUpdatePart */
                if ($cachedVoiceState = yield $channel->members->cacheGet($data->user_id)) {
                    // Copy
                    $oldVoiceState = clone $cachedVoiceState;
                    if ($cachedVoiceState->channel_id == $data->channel_id) {
                        // Move
                        $statePart = $cachedVoiceState;
                        // Update
                        $statePart->fill((array) $data);
                    }
                }

                if ($channel->id == $data->channel_id) {
                    // Add/update this member to the voice channel
                    yield $channel->members->cache->set($data->user_id, $statePart);
                } else {
                    // Remove each voice channels containing this member
                    yield $channel->members->cache->delete($data->user_id);
                }
            }

            if (isset($data->member)) {
                $this->cacheMember($guild->members, (array) $data->member);
                $this->cacheUser($data->member->user);
            }
        }

        return [$statePart, $oldVoiceState];
    }
}
