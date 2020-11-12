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
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;

class VoiceStateUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $state = $this->factory->create(VoiceStateUpdatePart::class, $data, true);

        if ($state->guild) {
            $guild = $state->guild;

            foreach ($guild->channels as $channel) {
                if (! $channel->allowVoice()) {
                    continue;
                }

                // Remove old member states
                if ($channel->members->has($state->user_id)) {
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

        $deferred->resolve($state);
    }
}
