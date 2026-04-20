<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-2022 David Cole <david.cole1340@gmail.com>
 * Copyright (c) 2020-present Valithor Obsidion <valithor@discordphp.org>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Channel\Message;

it('can delete all reactions from a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()
            ->sendMessage('testing delete all reactions')
            ->then(
                fn (Message $message) => \React\Promise\all([$message->react('😝'), $message->react('🤪')])
                    ->then(fn () => $message)
            )
            ->then(fn (Message $message) => $message->deleteAllReactions(Message::REACT_DELETE_ALL))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can delete own reaction', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()
            ->sendMessage('testing deleting self reaction')
            ->then(fn (Message $message) => $message->react('🤪')->then(fn () => $message))
            ->then(fn (Message $message) => $message->deleteOwnReaction('🤪'))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can delete a specific user reaction', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()
            ->sendMessage('testing deleting reaction of user')
            ->then(fn (Message $message) => $message->react('🤪')->then(fn () => $message))
            ->then(fn (Message $message) => $message->deleteUserReaction('🤪', $discord->id))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can delete all reactions for a specific emoji', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()
            ->sendMessage('testing deleting of single reaction')
            ->then(fn (Message $message) => $message->react('🤪')->then(fn () => $message))
            ->then(fn (Message $message) => $message->deleteEmojiReactions('🤪'))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

