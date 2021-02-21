<?php

declare(strict_types=1);

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Carbon\Carbon;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\Parts\User\Member;

final class EmptyMessageTest extends DiscordTestCase
{
    public function testCanSendMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $content = 'Hello, world! From PHPunit';

            $this->channel()->sendMessage($content)->done(function (Message $message) use ($resolve, $content) {
                $this->assertEquals($content, $message->content);
                $this->assertInstanceOf(Carbon::class, $message->timestamp);
                $this->assertNull($message->edited_timestamp);
                $resolve($message);
            });
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCanReplyToMessage(Message $message)
    {
        return wait(function (Discord $discord, $resolve) use ($message) {
            $message->reply('replying to my message')->done(function (Message $new_message) use ($resolve, $message) {
                $this->assertEquals('replying to my message', $new_message->content);
                $this->assertInstanceOf(Message::class, $new_message->referenced_message);
                $this->assertEquals($message->id, $new_message->referenced_message->id);
                $resolve($new_message);
            });
        });
    }

    public function testCanEditMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $content = 'Message edit with PHPunit';

            $this->channel()->sendMessage('before edit')->then(function (Message $message) use ($content, $resolve) {
                $message->content = $content;
                $message->channel->messages->save($message)->done(function (Message $message) use ($resolve, $content) {
                    $this->assertEquals($content, $message->content);
                    $this->assertNotNull($message->edited_timestamp);
                    $resolve($message);
                });
            });
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCheckMessageFlagsFalse(Message $message)
    {
        $this->assertFalse($message->crossposted);
        $this->assertFalse($message->is_crosspost);
        $this->assertFalse($message->suppress_embeds);
        $this->assertFalse($message->source_message_deleted);
        $this->assertFalse($message->urgent);
    }

    /**
     * @depends testCanSendMessage
     */
    public function testChannelAttribute(Message $message)
    {
        $this->assertInstanceOf(Channel::class, $message->channel);
        $this->assertEquals($message->channel_id, $message->channel->id);
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCollectionsEmpty(Message $message)
    {
        $this->assertInstanceOf(Collection::class, $message->mentions);
        $this->assertEquals(0, $message->mentions->count());

        $this->assertInstanceOf(Collection::class, $message->mention_roles);
        $this->assertEquals(0, $message->mention_roles->count());

        $this->assertInstanceOf(Collection::class, $message->reactions);
        $this->assertEquals(0, $message->reactions->count());

        $this->assertInstanceOf(Collection::class, $message->mention_channels);
        $this->assertEquals(0, $message->mention_channels->count());

        $this->assertInstanceOf(Collection::class, $message->embeds);
        $this->assertEquals(0, $message->embeds->count());
    }

    /**
     * @depends testCanSendMessage
     */
    public function testAuthorAttribute(Message $message)
    {
        $this->assertInstanceOf(Member::class, $message->author);
        $this->assertEquals($message->author->id, DiscordSingleton::get()->id);
    }

    /**
     * @depends testCanEditMessage
     */
    public function testEditedTimestampAttribute(Message $message)
    {
        $this->assertInstanceOf(Carbon::class, $message->edited_timestamp);
    }

    public function testDelayedReply()
    {
        return wait(function (Discord $discord, $resolve) {
            // Random delay between 0 and 5s.
            $delay = (int) ((mt_rand() / mt_getrandmax()) * 5000);
            $start = microtime(true);

            $this->channel()->sendMessage('testing delayed reply')->then(function (Message $message) use ($delay) {
                return $message->delayedReply('delayed reply to message', $delay);
            })->done(function (Message $message) use ($delay, $start, $resolve) {
                $stop = microtime(true);
                $diff = $stop - $start;

                $this->assertGreaterThanOrEqual($delay / 1000, $diff);
                $resolve();
            });
        }, 10);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCanReactWithString()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing reactions')->then(function (Message $message) {
                return $message->react('😀');
            })->done(function () use ($resolve) {
                $resolve();
            });
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCanAddEmbed(Message $message)
    {
        return wait(function (Discord $discord, $resolve) use ($message) {
            $this->channel()->sendMessage('testing adding embed')->then(function (Message $message) use ($discord) {
                $embed = new Embed($discord);
                $embed->setTitle('Test embed')
                    ->addFieldValues('Field name', 'Field value', true);

                return $message->addEmbed($embed);
            })->done(function (Message $message) use ($resolve) {
                $this->assertEquals(1, $message->embeds->count());

                /** @var Embed */
                $embed = $message->embeds->first();
                $this->assertEquals('Test embed', $embed->title);
                $this->assertEquals(1, $embed->fields->count());

                /** @var \Discord\Parts\Embed\Field */
                $field = $embed->fields->first();
                $this->assertEquals('Field name', $field->name);
                $this->assertEquals('Field value', $field->value);
                $this->assertEquals(true, $field->inline);

                $resolve();
            });
        });
    }

    /**
     * @depends testCanSendMessage
     */
    public function testCanDeleteMessage(Message $message)
    {
        return wait(function (Discord $discord, $resolve) use ($message) {
            $message->channel->messages->delete($message)->done(function (Message $message) use ($resolve) {
                $this->assertFalse($message->created);
                $resolve();
            });
        });
    }
}
