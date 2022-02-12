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

use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Helpers\Collection;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Invite;

/**
 * @covers \Discord\Parts\Channel\Channel
 */
final class ChannelTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Parts\Channel\Channel::pinMessage
     * @covers \Discord\Parts\Channel\Channel::getPinnedMessages
     */
    public function testCanPinMessageAndGetPinnedMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing pin message')
                ->then(function (Message $message) {
                    return $this->channel()->pinMessage($message)
                        ->then(function () {
                            return $this->channel()->getPinnedMessages();
                        })
                        ->then(function (Collection $messages) use ($message) {
                            $this->assertGreaterThan(0, $messages->count());
                            $this->assertContains($message->id, $messages->map(function ($message) {
                                return $message->id;
                            }));
                        });
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::pinMessage
     * @covers \Discord\Parts\Channel\Channel::unpinMessage
     * @covers \Discord\Parts\Channel\Channel::getPinnedMessages
     */
    public function testCanPinAndUnpinMessageAndCheckItIsUnpinned()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing pin message')
                ->then(function (Message $message) {
                    return $this->channel()->pinMessage($message)
                        ->then(function () use ($message) {
                            return $this->channel()->unpinMessage($message);
                        })
                        ->then(function () {
                            return $this->channel()->getPinnedMessages();
                        })
                        ->then(function (Collection $messages) use ($message) {
                            $this->assertNotContains($message->id, $messages->map(function ($message) {
                                return $message->id;
                            }));
                        });
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::getMessage
     */
    public function testCanGetMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing get message')
                ->then(function (Message $message) {
                    return $this->channel()->messages->fetch($message->id)
                        ->then(function (Message $getMessage) use ($message) {
                            $this->assertEquals($getMessage->id, $message->id);
                        });
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::createInvitek
     */
    public function testCanCreateInvite()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->createInvite()
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::deleteMessages
     */
    public function testCanDeleteMessagesWithZeroMessages()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->deleteMessages([])
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::deleteMessages
     */
    public function testCanDeleteMessagesWithOneMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing delete one message')
                ->then(function (Message $message) {
                    return $this->channel()->deleteMessages([$message]);
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::deleteMessages
     */
    public function testCanDeleteMessagesWithMultipleMessages()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing delete 1/2 message')
                ->then(function (Message $m1) {
                    return $this->channel()->sendMessage('testing delete 2/2 message')
                        ->then(function (Message $m2) use ($m1) {
                            return $this->channel()->deleteMessages([$m1, $m2]);
                        });
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::limitDelete
     */
    public function testCanLimitDeleteMessages()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->limitDelete(5)
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::getMessageHistory
     */
    public function testCanGetMessageHistory()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->getMessageHistory([])
                ->then(function ($messages) {
                    $this->assertInstanceOf(Collection::class, $messages);

                    if ($messages->count() < 1) {
                        $this->markTestSkipped('no messages were present when gettign message history - could not check if collection contained message objects.');

                        return;
                    }

                    foreach ($messages as $message) {
                        $this->assertInstanceOf(Message::class, $message);
                    }
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::getInvites
     */
    public function testCanGetInvites()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->getInvites()
                ->then(function (Collection $invites) {
                    $this->assertInstanceOf(Collection::class, $invites);

                    if ($invites->count() < 1) {
                        $this->markTestSkipped('no invites were present when getting invites - could not check if collection contained invite objects.');

                        return;
                    }

                    foreach ($invites as $invite) {
                        $this->assertInstanceOf(Invite::class, $invite);
                    }
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::editMessage
     */
    public function testCanEditMessageThroughChannel()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->sendMessage('testing edit through channel')
                ->then(function (Message $message) {
                    return $message->edit(MessageBuilder::new()->setContent('new content'))
                        ->then(function (Message $updatedMessage) use ($message) {
                            $this->assertEquals('new content', $updatedMessage->content);
                            $this->assertEquals($message->id, $updatedMessage->id);
                        });
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::sendFile
     */
    public function testCanSendFile()
    {
        return wait(function (Discord $discord, $resolve) {
            // upload readme
            $baseDir = dirname(dirname(dirname((new ReflectionClass(Discord::class))->getFileName())));
            $this->channel()->sendMessage(MessageBuilder::new()->addFile($baseDir.DIRECTORY_SEPARATOR.'README.md'))
                ->then(function (Message $message) {
                    $this->assertEquals(1, count($message->attachments));
                })
                ->done($resolve, $resolve);
        });
    }

    /**
     * @doesNotPerformAssertions
     * @covers \Discord\Parts\Channel\Channel::broadcastTyping
     */
    public function testCanBroadcastTyping()
    {
        return wait(function (Discord $discord, $resolve) {
            $this->channel()->broadcastTyping()
                ->done($resolve, $resolve);
        });
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::allowVoice
     */
    public function testTextChannelDoesNotAllowVoice()
    {
        $this->assertFalse($this->channel()->allowVoice());
        $this->assertTrue($this->channel()->allowText());
    }

    /**
     * @covers \Discord\Parts\Channel\Channel::allowVoice
     */
    public function testVoiceChannelDoesNotAllowText()
    {
        /**
         * @var Channel
         */
        $vc = $this->channel()->guild->channels->filter(function ($channel) {
            return $channel->type == Channel::TYPE_VOICE;
        })->first();

        $this->assertFalse($vc->allowText());
        $this->assertTrue($vc->allowVoice());
    }
}
