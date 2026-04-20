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

use Carbon\Carbon;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\User;

it('can send a plain text message', function () {
    return wait(function (Discord $discord, $resolve) {
        $content = 'Hello, world! From PHPunit';

        $this->channel()->sendMessage($content)
            ->then(function (Message $message) use ($content) {
                expect($message->content)->toBe($content);
                expect($message->timestamp)->toBeInstanceOf(Carbon::class);
                expect($message->edited_timestamp)->toBeNull();
            })
            ->then($resolve, $resolve);
    });
});

it('can reply to a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $content = 'Hello, world! From PHPunit';
        $this->channel()->sendMessage($content)
            ->then(
                fn (Message $message) => $message->reply('replying to my message')
                ->then(function (Message $reply) use ($message) {
                    expect($reply->content)->toBe('replying to my message');
                    expect($reply->referenced_message)->toBeInstanceOf(Message::class);
                    expect($reply->referenced_message->id)->toBe($message->id);
                })
            )
            ->then($resolve, $resolve);
    });
});

it('can edit a message', function () {
    return wait(function (Discord $discord, $resolve) {
        $content = 'Message edit with PHPunit';

        $this->channel()->sendMessage('before edit')
            ->then(function (Message $message) use ($content) {
                $message->content = $content;

                return $message->save($content)->then(function (Message $updated) use ($content) {
                    expect($updated->content)->toBe($content);
                    expect($updated->edited_timestamp)->not->toBeNull();
                });
            })
            ->then($resolve, $resolve);
    });
});

it('message flags are false for a normal message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('flag check')
            ->then(function (Message $message) {
                expect($message->crossposted)->toBeFalse();
                expect($message->is_crosspost)->toBeFalse();
                expect($message->suppress_embeds)->toBeFalse();
                expect($message->source_message_deleted)->toBeFalse();
                expect($message->urgent)->toBeFalse();
            })
            ->then($resolve, $resolve);
    });
});

it('channel attribute resolves to the correct channel', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('channel attr')
            ->then(function (Message $message) {
                expect($message->channel)->toBeInstanceOf(Channel::class);
                expect($message->channel->id)->toBe($message->channel_id);
            })
            ->then($resolve, $resolve);
    });
});

it('collection attributes are empty on a plain message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('collections empty')
            ->then(function (Message $message) {
                expect($message->mentions)->toBeInstanceOf(Collection::class);
                expect($message->mentions->count())->toBe(0);
                expect($message->mention_roles)->toBeInstanceOf(Collection::class);
                expect($message->mention_roles->count())->toBe(0);
                expect($message->reactions)->toBeInstanceOf(Collection::class);
                expect($message->reactions->count())->toBe(0);
                expect($message->mention_channels)->toBeInstanceOf(Collection::class);
                expect($message->mention_channels->count())->toBe(0);
                expect($message->embeds)->toBeInstanceOf(Collection::class);
                expect($message->embeds->count())->toBe(0);
            })
            ->then($resolve, $resolve);
    });
});

it('author attribute resolves to the bot user', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('author attr')
            ->then(function (Message $message) {
                expect($message->author)->toBeInstanceOf(User::class);
                expect($message->author->id)->toBe(DiscordSingleton::get()->id);
            })
            ->then($resolve, $resolve);
    });
});

it('edited_timestamp is a Carbon instance after editing', function () {
    return wait(function (Discord $discord, $resolve) {
        $content = 'Message edit with PHPunit';
        $this->channel()->sendMessage('before edit')
            ->then(function (Message $message) use ($content) {
                $message->content = $content;

                return $message->save($content);
            })
            ->then(fn (Message $message) => expect($message->edited_timestamp)->toBeInstanceOf(Carbon::class))
            ->then($resolve, $resolve);
    });
});

it('can send a delayed reply', function () {
    return wait(function (Discord $discord, $resolve) {
        $delay = (int) ((mt_rand() / mt_getrandmax()) * 5000);
        $start = microtime(true);

        $this->channel()->sendMessage('testing delayed reply')
            ->then(fn (Message $message) => $message->delayedReply('delayed reply to message', $delay))
            ->then(function (Message $message) use ($delay, $start) {
                expect(microtime(true) - $start)->toBeGreaterThanOrEqual($delay / 1000);
            })
            ->then($resolve, $resolve);
    }, 10);
});

it('can react to a message with an emoji', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing reactions')
            ->then(fn (Message $message) => $message->react('😀'))
            ->then($resolve, $resolve);
    });
})->doesNotPerformAssertions();

it('can add an embed to an existing message', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing adding embed')
            ->then(function (Message $message) use ($discord) {
                $embed = new Embed($discord);
                $embed->setTitle('Test embed')
                    ->addFieldValues('Field name', 'Field value', true);

                return $message->addEmbed($embed);
            })
            ->then(function (Message $message) {
                expect($message->embeds->count())->toBe(1);

                /** @var Embed */
                $embed = $message->embeds->first();
                expect($embed->title)->toBe('Test embed');
                expect($embed->fields->count())->toBe(1);

                /** @var \Discord\Parts\Embed\Field */
                $field = $embed->fields->first();
                expect($field->name)->toBe('Field name');
                expect($field->value)->toBe('Field value');
                expect($field->inline)->toBeTrue();
            })
            ->then($resolve, $resolve);
    });
});

it('can delete a message through the repository', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('delete through repo')
            ->then(fn (Message $message) => $message->channel->messages->delete($message))
            ->then(fn (Message $message) => expect($message->created)->toBeFalse())
            ->then($resolve, $resolve);
    });
});

it('can delete a message through the part', function () {
    return wait(function (Discord $discord, $resolve) {
        $this->channel()->sendMessage('testing delete through part')
            ->then(fn (Message $message) => $message->delete())
            ->then($resolve);
    });
})->doesNotPerformAssertions();

