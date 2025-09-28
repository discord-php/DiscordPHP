<?php

declare(strict_types=1);

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
use Discord\Parts\Guild\Guild;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;

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
     * @inheritDoc
     */
    public function handle($data)
    {
        $oldVoiceState = null;
        /** @var VoiceStateUpdatePart */
        $statePart = $this->factory->part(VoiceStateUpdatePart::class, (array) $data->d, true);

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->d->guild_id)) {
            /** @var Guild $guild */

            // Preload target new voice state channel
            yield $guild->channels->cacheGet($data->d->channel_id);

            foreach ($guild->channels as $channel) {
                /** @var Channel $channel */
                if (! $channel->isVoiceBased()) {
                    continue;
                }

                /** @var ?VoiceStateUpdatePart */
                if ($cachedVoiceState = yield $channel->members->cacheGet($data->d->user_id)) {
                    /** @var VoiceStateUpdatePart $cachedVoiceState*/

                    // Copy
                    $oldVoiceState = clone $cachedVoiceState;
                    if ($cachedVoiceState->channel_id == $data->d->channel_id) {
                        // Move
                        $statePart = $cachedVoiceState;
                        // Update
                        $statePart->fill((array) $data->d);
                    }
                }

                if ($channel->id == $data->d->channel_id) {
                    // Add/update this member to the voice channel
                    yield $channel->members->cache->set($data->d->user_id, $statePart);
                } else {
                    // Remove each voice channels containing this member
                    yield $channel->members->cache->delete($data->d->user_id);
                }
            }

            if (isset($data->d->member)) {
                $this->cacheMember($guild->members, (array) $data->d->member);
                $this->cacheUser($data->d->member->user);
            }

            $guild->voice_states->cache->set($data->d->user_id, $statePart);
        }

        return [$statePart, $oldVoiceState];
    }
}
