<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 */

use Discord\Builders\MessageBuilder;
use Discord\Parts\Channel\Message;
use Discord\Parts\Channel\Message\MessageReference;
use PHPUnit\Framework\TestCase;

final class MessageBuilderTest extends TestCase
{
    public function testMessageReferencePartSerialization()
    {
        $discord = getMockDiscord();
        $factory = $discord->getFactory();

        $messageReference = $factory->part(MessageReference::class, [
            'type' => MessageReference::TYPE_DEFAULT,
            'message_id' => '111',
            'channel_id' => '222',
            'guild_id' => null,
            'fail_if_not_exists' => true,
        ], true);

        $builder = MessageBuilder::new();
        $builder->setMessageReference($messageReference);

        $payload = json_decode($builder->getPayloadJson(), true);

        $this->assertArrayHasKey('message_reference', $payload);
        $this->assertSame('111', $payload['message_reference']['message_id']);
        $this->assertSame('222', $payload['message_reference']['channel_id']);
        $this->assertArrayHasKey('type', $payload['message_reference']);
        $this->assertSame(MessageReference::TYPE_DEFAULT, $payload['message_reference']['type']);
    }

    public function testReplyAndForwardLegacyPaths()
    {
        $discord = getMockDiscord();
        $factory = $discord->getFactory();

        $message = $factory->part(Message::class, [
            'id' => '333',
            'channel_id' => '444',
        ], true);

        // Reply (legacy setReplyTo) should produce message_reference without a type key
        $builder = MessageBuilder::new();
        $builder->setReplyTo($message);
        $payload = json_decode($builder->getPayloadJson(), true);

        $this->assertArrayHasKey('message_reference', $payload);
        $this->assertSame('333', $payload['message_reference']['message_id']);
        $this->assertSame('444', $payload['message_reference']['channel_id']);
        $this->assertArrayNotHasKey('type', $payload['message_reference']);

        // Forward (legacy setForward) should include the forward type
        $builder2 = MessageBuilder::new();
        $builder2->setForward($message);
        $payload2 = json_decode($builder2->getPayloadJson(), true);

        $this->assertArrayHasKey('message_reference', $payload2);
        $this->assertSame(Message::REFERENCE_FORWARD, $payload2['message_reference']['type']);
        $this->assertSame('333', $payload2['message_reference']['message_id']);
        $this->assertSame('444', $payload2['message_reference']['channel_id']);
    }
}
