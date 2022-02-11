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
use Discord\Helpers\Deferred;

/**
 * @see https://discord.com/developers/docs/topics/gateway#voice-state-update
 */
class VoiceStateUpdate extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $state = $this->factory->create(VoiceStateUpdatePart::class, $data, true);
        $old_state = null;

        if ($state->guild) {
            $guild = $state->guild;

            foreach ($guild->channels as $channel) {
                if (! $channel->allowVoice()) {
                    continue;
                }

                // Remove old member states
                if ($channel->members->has($state->user_id)) {
                    $old_state = $channel->members->offsetGet($state->user_id);
                    $channel->members->offsetUnset($state->user_id);
                }

                // Add member state to new channel
                if ($channel->id == $state->channel_id) {
                    $channel->members->offsetSet($state->user_id, $state);
                }

                $guild->channels->offsetSet($channel->id, $channel);
            }

            $this->discord->guilds->offsetSet($state->guild->id, $state->guild);
        }

        $this->cacheUser($data->member->user);

        $deferred->resolve([$state, $old_state]);
    }
}
