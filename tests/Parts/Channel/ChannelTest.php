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

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Channel\Message;

it('can pin a message and retrieve pinned messages', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing pin message')
            ->then(
                fn (Message $message) => $this->channel()->pinMessage($message)
                    ->then(fn () => $this->channel()->getPinnedMessages())
                    ->then(function (Collection $messages) use ($message) {
                        expect($messages->count())->toBeGreaterThan(0);
                        expect(in_array($message->id, $messages->map(fn ($m) => $m->id)->toArray()))->toBeTrue();
                    })
            )
            ->then($resolve, $resolve);
    });
});

it('can pin and unpin a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing pin message')
            ->then(
                fn (Message $message) => $this->channel()->pinMessage($message)
                    ->then(fn () => $this->channel()->unpinMessage($message))
                    ->then(fn () => $this->channel()->getPinnedMessages())
                    ->then(fn (Collection $messages) => expect(in_array($message->id, $messages->map(fn ($m) => $m->id)->toArray()))->toBeFalse())
            )
            ->then($resolve, $resolve);
    });
});

it('can fetch a message by ID', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing get message')
            ->then(
                fn (Message $message) => $this->channel()->messages->fetch($message->id)
                    ->then(function (Message $fetched) use ($message) {
                        expect($fetched->id)->toBe($message->id);
                    })
            )
            ->then($resolve, $resolve);
    });
});

it('can create an invite', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->createInvite()
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can bulk delete zero messages', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->deleteMessages([])
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can bulk delete a single message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing delete one message')
            ->then(fn (Message $message) => $this->channel()->deleteMessages([$message]))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can bulk delete multiple messages', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing delete 1/2 message')
            ->then(fn (Message $m1) => $this->channel()->sendMessage('testing delete 2/2 message')
            ->then(fn (Message $m2) => $this->channel()->deleteMessages([$m1, $m2])))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can limit-delete messages', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->limitDelete(5)
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can retrieve message history', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->getMessageHistory([])
            ->then(function (Collection $messages) {
                expect($messages)->toBeInstanceOf(Collection::class);

                if ($messages->count() < 1) {
                    $this->markTestSkipped('no messages were present when getting message history');

                    return;
                }

                foreach ($messages as $message) {
                    expect($message)->toBeInstanceOf(Message::class);
                }
            })
            ->then($resolve, $resolve);
    });
});

it('can retrieve channel invites', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->invites->freshen()
            ->then(function (Collection $invites) {
                expect($invites)->toBeInstanceOf(Collection::class);

                if ($invites->count() < 1) {
                    $this->markTestSkipped('no invites were present when getting invites');

                    return;
                }

                foreach ($invites as $invite) {
                    expect($invite)->toBeInstanceOf(Invite::class);
                }
            })
            ->then($resolve, $resolve);
    });
});

it('can edit a message through the channel', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing edit through channel')
            ->then(
                fn (Message $message) => $message->edit(MessageBuilder::new()->setContent('new content'))
                ->then(function (Message $updated) use ($message) {
                    expect($updated->content)->toBe('new content');
                    expect($updated->id)->toBe($message->id);
                })
            )
            ->then($resolve, $resolve);
    });
});

it('can send a file attachment', function () {
    return wait(function (Discord $discord, $resolve) {
        $baseDir = dirname(dirname(dirname((new ReflectionClass(Discord::class))->getFileName())));
        $this->channel()->sendMessage(MessageBuilder::new()->addFile($baseDir.DIRECTORY_SEPARATOR.'README.md'))
            ->then(fn (Message $message) => expect(count($message->attachments))->toBe(1))
            ->then($resolve, $resolve);
    });
});

it('can broadcast typing', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->broadcastTyping()
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('text channel is text-based', function () {
    expect($this->channel()->isTextBased())->toBeTrue();
});

it('voice channel is voice-based', function () {
    /** @var Channel */
    $vc = $this->channel()->guild->channels->filter(fn ($channel) => $channel->type === Channel::TYPE_GUILD_VOICE)->first();

    expect($vc->isVoiceBased())->toBeTrue();
});

