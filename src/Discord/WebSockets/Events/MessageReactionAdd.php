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

use Discord\Parts\WebSockets\MessageReaction;
use Discord\WebSockets\Event;
use Discord\Helpers\Deferred;
use Discord\Parts\User\User;

class MessageReactionAdd extends Event
{
    /**
     * @inheritdoc
     */
    public function handle(Deferred &$deferred, $data): void
    {
        $reaction = new MessageReaction($this->discord, (array) $data, true);

        if ($channel = $reaction->channel) {
            if ($message = $channel->messages->offsetGet($reaction->message_id)) {
                $addedReaction = false;

                foreach ($message->reactions as $react) {
                    if ($react->id == $reaction->reaction_id) {
                        ++$react->count;

                        if ($reaction->user_id == $this->discord->id) {
                            $react->me = true;
                        }

                        $addedReaction = true;
                        break;
                    }
                }

                // New reaction added
                if (! $addedReaction) {
                    $message->reactions->push($message->reactions->create([
                        'count' => 1,
                        'me' => $reaction->user_id == $this->discord->id,
                        'emoji' => $reaction->emoji->getRawAttributes(),
                    ], true));
                }
            }
        }

        // User caching
        if (isset($data->member->user)) {
            if ($user = $this->discord->users->get('id', $data->member->user->id)) {
                $user->fill((array) $data->member->user);
            } else {
                $this->discord->users->pushItem($this->factory->part(User::class, (array) $data->member->user, true));
            }
        }

        $deferred->resolve($reaction);
    }
}
