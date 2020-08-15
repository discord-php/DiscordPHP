<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016 David Cole <david@team-reflex.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\WebSockets\Events;

use Discord\Parts\Channel\Channel;
use Discord\Parts\WebSockets\VoiceStateUpdate as VoiceStateUpdatePart;
use Discord\WebSockets\Event;
use React\Promise\Deferred;

class VoiceStateUpdate extends Event
{
    /**
     * {@inheritdoc}
     */
    public function handle(Deferred $deferred, $data)
    {
        $state = $this->factory->create(VoiceStateUpdatePart::class, $data, true);

        if ($state->guild) {
            $guild = $state->guild;

            // Remove old member states
            foreach ($guild->channels as $channel) {
                if ($channel->getChannelType() !== Channel::TYPE_VOICE) {
                    continue;
                }

                foreach ($channel->members as $member) {
                    if ($member->user_id == $state->user_id) {
                        $channel->members->pull($member->user_id);
                    }
                }

                $guild->channels->push($channel);
            }

            // Add member state to new channel
            if ($state->channel_id && $channel = $guild->channels->get('id', $state->channel_id)) {
                $channel->members->push($state);

                $guild->channels->push($channel);
            }

            $this->discord->guilds->push($state->guild);
        }

        $deferred->resolve($state);
    }
}
