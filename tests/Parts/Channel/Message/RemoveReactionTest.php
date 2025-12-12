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
     * @covers \Discord\Parts\Channel\Message::deleteAllReactions
     */
    public function testDeleteAllReactions()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing delete all reactions')
                ->then(function (Message $message) {
                    return \React\Promise\all([$message->react('😝'), $message->react('🤪')])
                        ->then(function () use ($message) {
                            return $message;
                        });
                })
                ->then(function (Message $message) {
                    return $message->deleteAllReactions(Message::REACT_DELETE_ALL);
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteOwnReaction
     */
    public function testDeleteSelfReaction()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting self reaction')
                ->then(fn (Message $message) => $message->react('🤪')->then(fn() => $message))
                ->then(fn (Message $message) => $message->deleteOwnReaction('🤪'))
                ->then($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteUserReaction
     */
    public function testDeleteReactionOfUser()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting reaction of user')
                ->then(fn (Message $message) => $message->react('🤪')->then(fn() => $message))
                ->then(fn (Message $message) => $message->deleteUserReaction('🤪', $discord->id))
                ->then($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Message::deleteEmojiReactions
     */
    public function testDeleteAllReactionsForEmoji()
    {
        return wait(function (Discord $discord, $resolve) {
            $this
                ->channel()
                ->sendMessage('testing deleting of single reaction')
                ->then(fn (Message $message) => $message->react('🤪')->then(fn() => $message))
                ->then(fn (Message $message) => $message->deleteEmojiReactions('🤪'))
                ->then($resolve, $resolve);
        });
    }
}
