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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;

it('can mention a channel in a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('mention channel <#'.$this->channel()->id.'>')
            ->then(function (Message $message) {
                expect($message->mention_channels->count())->toBe(1);
                expect($message->mention_channels->first())->toBeInstanceOf(Channel::class);
                expect($message->mention_channels->first()->id)->toBe($this->channel()->id);
            })
            ->then($resolve, $resolve);
    });
});

it('can crosspost a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $channel = $this->channel();
        expect($channel->type)->toBe(Channel::TYPE_GUILD_ANNOUNCEMENT);
        $channel->sendMessage('crossposting message')
            ->then(fn (Message $message) => $message->crosspost())
            ->then(fn ($message) => expect($message)->toBeInstanceOf(Message::class))
            ->then($resolve, $resolve);
    }, 10, fn () => $this->markTestIncomplete('Crosspost has likely hit ratelimit.'));
});

