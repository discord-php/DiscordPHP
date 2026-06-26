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
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Message\MessageReference;

final class MessageBuilderTest extends DiscordTestCase
{
    public function testSendMessageReplyAndForwardUsingMessageReference()
    {
        return wait(function (Discord $discord, $resolve) {
            // Send an initial message
            $this->channel()->sendMessage(MessageBuilder::new()->setContent('MessageBuilder integration test'))
                ->then(function (Message $original) use ($resolve) {
                    $this->assertInstanceOf(Message::class, $original);

                    $original->getDiscord()->getLogger()->debug('Send a reply using setMessageReference with default type');

                    $replyBuilder = MessageBuilder::new()
                        ->setContent('Reply to message')
                        ->setMessageReference($original, MessageReference::TYPE_DEFAULT, false);

                    $original->getDiscord()->getLogger()->debug('Reply: '.json_encode($replyBuilder));

                    $this->channel()->sendMessage($replyBuilder)
                        ->then(function (Message $reply) use ($original, $resolve) {
                            $this->assertNotNull($reply->message_reference);
                            $this->assertSame($original->id, $reply->message_reference->message_id);
                            $this->assertSame(MessageReference::TYPE_DEFAULT, $reply->message_reference->type);

                            $original->getDiscord()->getLogger()->debug('Send a forward using setMessageReference with forward type');
                            $forwardBuilder = MessageBuilder::new()
                                ->setContent('Forward of message') // Should be dropped
                                ->setMessageReference($original, MessageReference::TYPE_FORWARD, false);

                            $original->getDiscord()->getLogger()->debug('Forward: '.json_encode($forwardBuilder));

                            $this->channel()->sendMessage($forwardBuilder)
                                ->then(function (Message $forward) use ($original, $resolve) {
                                    $this->assertNotNull($forward->message_reference);
                                    $this->assertSame($original->id, $forward->message_reference->message_id);
                                    $this->assertSame(MessageReference::TYPE_FORWARD, $forward->message_reference->type);

                                    $resolve(true);
                                }, $resolve);
                        }, $resolve);
                }, $resolve);
        }, 15);
    }
}
