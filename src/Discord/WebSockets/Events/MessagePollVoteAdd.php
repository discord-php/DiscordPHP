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

use Discord\Parts\Channel\Poll\PollAnswer;
use Discord\WebSockets\Event;

/**
 * @link https://discord.com/developers/docs/topics/gateway-events#message-poll-vote-add-message-poll-vote-add-fields
 *
 * @since 10.0.0
 */
class MessagePollVoteAdd extends Event
{
    /**
     * {@inheritDoc}
     */
    public function handle($data)
    {
        return new PollAnswer($this->discord, (array) $data, true);
    }
}
