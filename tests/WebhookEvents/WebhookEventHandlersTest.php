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
use Discord\Parts\Channel\DM;
use Discord\Parts\Channel\GameDirectMessage;
use Discord\Parts\Channel\Message as ChannelMessage;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Lobby\Message;
use Discord\Parts\User\User;
use Discord\Parts\WebSockets\ApplicationAuthorized as ApplicationAuthorizedPart;
use Discord\WebSockets\Event;
use Discord\WebSockets\Events\ApplicationAuthorized;
use Discord\WebSockets\Events\ApplicationDeauthorized;
use Discord\WebSockets\Events\GameDirectMessageCreate;
use Discord\WebSockets\Events\GameDirectMessageUpdate;
use Discord\WebSockets\Events\LobbyMessageDelete;
use Discord\WebSockets\Events\LobbyMessageUpdate;
use Discord\WebSockets\Handlers;

final class WebhookEventHandlersTest extends DiscordTestCase
{
    public function testEveryWebhookEventHasAHandler()
    {
        $handlers = new Handlers();

        foreach ([Event::APPLICATION_AUTHORIZED, Event::APPLICATION_DEAUTHORIZED, Event::LOBBY_MESSAGE_CREATE, Event::LOBBY_MESSAGE_UPDATE, Event::LOBBY_MESSAGE_DELETE, Event::GAME_DIRECT_MESSAGE_CREATE, Event::GAME_DIRECT_MESSAGE_UPDATE, Event::GAME_DIRECT_MESSAGE_DELETE, Event::ENTITLEMENT_CREATE] as $event) {
            $this->assertNotNull($handlers->getHandler($event), $event);
        }
    }

    public function testAnAuthorizationCarriesTheUserAndScopes()
    {
        $mock = getMockDiscord();

        /** @var ApplicationAuthorizedPart $authorized */
        $authorized = (new ApplicationAuthorized($mock))->handle($this->decode([
            'integration_type' => 1,
            'scopes' => ['applications.commands'],
            'user' => $this->user('5', 'player'),
        ]));

        $this->assertSame(1, $authorized->integration_type);
        $this->assertSame(['applications.commands'], $authorized->scopes);
        $this->assertSame('player', $authorized->user->username);
        $this->assertNull($authorized->guild);
        $this->assertNotNull($mock->users->get('id', '5'), 'the user is cached');
    }

    public function testADeauthorizationIsTheUser()
    {
        $user = (new ApplicationDeauthorized(getMockDiscord()))->handle($this->decode(['user' => $this->user('5', 'player')]));

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('5', $user->id);
    }

    public function testAnEditedLobbyMessageKeepsItsTimestamps()
    {
        /** @var Message $message */
        $message = (new LobbyMessageUpdate(getMockDiscord()))->handle($this->decode([
            'id' => '9',
            'type' => 0,
            'content' => 'noice',
            'lobby_id' => '1',
            'channel_id' => '2',
            'author' => $this->user('5', 'player'),
            'edited_timestamp' => '2025-08-05T20:39:19.557970+00:00',
            'flags' => 0,
            'timestamp' => '2025-08-05T20:38:43.660000+00:00',
        ]));

        $this->assertSame('noice', $message->content);
        $this->assertSame('2025-08-05', $message->timestamp->toDateString());
        $this->assertTrue($message->edited_timestamp->greaterThan($message->timestamp));
    }

    public function testADeletedLobbyMessageHasItsIds()
    {
        $message = (new LobbyMessageDelete(getMockDiscord()))->handle($this->decode(['id' => '9', 'lobby_id' => '1']));

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame(['9', '1'], [$message->id, $message->lobby_id]);
        $this->assertFalse($message->created);
    }

    public function testADirectMessageFindsTheOtherUserInItsChannel()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->client();

            /** @var GameDirectMessage $message */
            $message = (new GameDirectMessageCreate($mock))->handle($this->decode([
                'id' => '9',
                'type' => 0,
                'content' => 'get in friend',
                'channel_id' => '20',
                'channel' => ['id' => '20', 'type' => 1, 'recipients' => [$this->user('5', 'author'), $this->user('6', 'friend')]],
                'author' => $this->user('5', 'author'),
                'timestamp' => '2025-08-14T18:09:37.947000+00:00',
                'application_id' => '7',
                'attachments' => [],
            ]));

            $this->assertSame('6', $message->recipient_id);
            // Everything else is typed the way a message is.
            $this->assertInstanceOf(ChannelMessage::class, $message);
            $this->assertInstanceOf(DM::class, $message->channel);
            $this->assertSame(0, $message->attachments->count());
            $this->assertSame('2025-08-14', $message->timestamp->toDateString());

            $message->updateModerationMetadata(['action' => 'hide', 'reason' => 'toxicity'])
                ->then(function () use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/partner-sdk/dms/5/6/messages/9/moderation-metadata', $driver->requests[0]['url']);
                    $this->assertSame(['action' => 'hide', 'reason' => 'toxicity'], $driver->requests[0]['content']);
                    $this->assertSame('Bot ', substr($driver->requests[0]['headers']['Authorization'], 0, 4));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testADirectMessageUsesItsRecipientIdWhenItHasOne()
    {
        [$mock] = $this->client();

        $message = (new GameDirectMessageUpdate($mock))->handle($this->decode([
            'id' => '9',
            'content' => 'almost ready to queue?',
            'channel_id' => '6',
            'author' => $this->user('5', 'author'),
            'recipient_id' => '6',
        ]));

        $this->assertSame('6', $message->recipient_id);
        // Without an attached channel, it is found the way a message's is.
        $this->assertInstanceOf(DM::class, $message->channel);
        $this->assertSame('6', $message->channel->id);
    }

    public function testADirectMessageWithoutItsOtherUserCannotBeModerated()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->client();

            $message = $mock->getFactory()->part(GameDirectMessage::class, ['id' => '9', 'author' => $this->decode($this->user('5', 'author'))], true);

            $message->updateModerationMetadata(['action' => 'hide'])
                ->then(
                    fn () => $this->fail('should reject without the other user'),
                    fn (\Throwable $e) => $this->assertInstanceOf(\DomainException::class, $e)
                )
                ->then(fn () => $this->assertSame([], $driver->requests))
                ->then($resolve, $resolve);
        });
    }

    public function testMessageMethodsThatNeedTheChannelRejectWithoutARequest()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->client();

            /** @var GameDirectMessage $message */
            $message = $mock->getFactory()->part(GameDirectMessage::class, ['id' => '9', 'channel_id' => '20', 'author' => $this->decode($this->user('5', 'author')), 'recipient_id' => '6'], true);

            $calls = [
                'startThread' => fn () => $message->startThread('thread'),
                'reply' => fn () => $message->reply('hi'),
                'crosspost' => fn () => $message->crosspost(),
                'delayedReply' => fn () => $message->delayedReply('hi', 10),
                'delayedDelete' => fn () => $message->delayedDelete(10),
                'react' => fn () => $message->react('👍'),
                'deleteReaction' => fn () => $message->deleteReaction(ChannelMessage::REACT_DELETE_ALL),
                'deleteAllReactions' => fn () => $message->deleteAllReactions(),
                'deleteOwnReaction' => fn () => $message->deleteOwnReaction('👍'),
                'deleteUserReaction' => fn () => $message->deleteUserReaction('👍', '6'),
                'deleteEmojiReactions' => fn () => $message->deleteEmojiReactions('👍'),
                'edit' => fn () => $message->edit(MessageBuilder::new()->setContent('hi')),
                'delete' => fn () => $message->delete(),
                'createReactionCollector' => fn () => $message->createReactionCollector(static fn () => true),
                'addEmbed' => fn () => $message->addEmbed(new Embed($mock)),
                'save' => fn () => $message->save(),
                'fetch' => fn () => $message->fetch(),
            ];

            $rejected = [];
            foreach ($calls as $method => $call) {
                $call()->then(null, function (\Throwable $e) use ($method, &$rejected) {
                    $this->assertInstanceOf(\BadMethodCallException::class, $e, $method);
                    $this->assertStringStartsWith("{$method}()", $e->getMessage());
                    $rejected[] = $method;
                });
            }

            $this->assertSame(array_keys($calls), $rejected);
            $this->assertFalse($message->isDeletable());
            $this->assertSame([], $driver->requests);
            $resolve();
        });
    }

    /**
     * A client whose requests are answered with 204.
     *
     * @return array{0: Discord, 1: object}
     */
    private function client(): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver(static fn () => null);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock, $driver];
    }

    private function user(string $id, string $username): array
    {
        return ['id' => $id, 'username' => $username, 'discriminator' => '0'];
    }

    /**
     * Decodes data the way a webhook payload is decoded.
     */
    private function decode(array $data): object
    {
        return json_decode(json_encode($data));
    }
}
