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

use Discord\Discord;
use Discord\Parts\Channel\Message;

final class RemoveReactionTest extends DiscordTestCase
{
    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteReaction
     */
    public function testDeleteAllReactions()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing delete all reactions')
                ->then(function (Message $message) {
                    return \React\Promise\all($message->react('😝'), $message->react('🤪'))
                        ->then(function () use ($message) {
                            return $message;
                        });
                })
                ->then(function (Message $message) {
                    return $message->deleteReaction(Message::REACT_DELETE_ALL);
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteReaction
     */
    public function testDeleteSelfReaction()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting self reaction')
                ->then(function (Message $message) {
                    return $message->react('🤪')->then(function () use ($message) {
                        return $message;
                    });
                })->then(function (Message $message) {
                    return $message->deleteReaction(Message::REACT_DELETE_ME, '🤪');
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteReaction
     */
    public function testDeleteReactionOfUser()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting reaction of user')
                ->then(function (Message $message) {
                    return $message->react('🤪')->then(function () use ($message) {
                        return $message;
                    });
                })->then(function (Message $message) use ($discord) {
                    return $message->deleteReaction(Message::REACT_DELETE_ID, '🤪', $discord->id);
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteReaction
     */
    public function testDeleteAllReactionsForEmoji()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting of single reaction')
                ->then(function (Message $message) {
                    return $message->react('🤪')->then(function () use ($message) {
                        return $message;
                    });
                })->then(function (Message $message) use ($discord) {
                    return $message->deleteReaction(Message::REACT_DELETE_EMOJI, '🤪');
                })
                ->done($resolve, $resolve);
        });
    }
}
