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
 * @link https://discord.com/developers/docs/topics/gateway#voice-state-update
 *
 * @since 2.1.3
 */
class VoiceStateUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle($data)
    {
        $statePart = $oldVoiceState = null;

        /** @var ?Guild */
        if ($guild = yield $this->discord->guilds->cacheGet($data->guild_id)) {
            /** @var ?Channel */
            foreach ($guild->channels as $channel) {
                if (! $channel->allowVoice()) {
                    continue;
                }

                /** @var ?VoiceStateUpdatePart */
                if ($oldVoiceState = yield $channel->members->cacheGet($data->user_id)) {
                    // Swap
                    $statePart = $oldVoiceState;
                    $oldVoiceState = clone $oldVoiceState;

                    $statePart->fill((array) $data);
                }

                if ($statePart === null) {
                    /** @var VoiceStateUpdatePart */
                    $statePart = $this->factory->part(VoiceStateUpdatePart::class, (array) $data, true);
                }

                if (! isset($data->channel_id)) {
                    // Remove old member states
                    yield $channel->members->cache->delete($data->user_id);
                    break;
                } elseif ($channel->id == $data->channel_id) {
                    // Add member state to new channel
                    $channel->members->set($data->user_id, $statePart);
                    break;
                }
            }
        }

        $this->cacheUser($data->member->user);

        return [$statePart, $oldVoiceState];
    }
}
