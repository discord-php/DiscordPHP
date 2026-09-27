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
use Discord\Parts\Channel\Invite;
use Discord\Parts\Lobby\Lobby;
use Discord\Parts\Lobby\Member;
use Discord\Parts\Lobby\Message;
use Discord\Parts\User\User;

final class LobbyTest extends DiscordTestCase
{
    public function testMembersAreLobbyMembers(): void
    {
        $lobby = getMockDiscord()->getFactory()->part(Lobby::class, [
            'id' => '1',
            'application_id' => '2',
            'members' => [
                ['id' => '5', 'flags' => Member::FLAG_CAN_LINK_LOBBY, 'additional_name' => 'Sir Lancelot'],
                ['id' => '6'],
            ],
        ], true);

        $this->assertCount(2, $lobby->members);

        $member = $lobby->members->get('id', '5');
        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame(Member::FLAG_CAN_LINK_LOBBY, $member->flags);
        $this->assertSame('Sir Lancelot', $member->additional_name);
    }

    public function testALobbyMessageHasATypedAuthor(): void
    {
        $message = getMockDiscord()->getFactory()->part(Message::class, [
            'id' => '9',
            'content' => 'gg',
            'lobby_id' => '1',
            'channel_id' => '1',
            'author' => (object) ['id' => '5', 'username' => 'player', 'discriminator' => '0'],
            'moderation_metadata' => ['action' => 'hide'],
        ], true);

        $this->assertInstanceOf(User::class, $message->author);
        $this->assertSame('player', $message->author->username);
        $this->assertSame(['action' => 'hide'], $message->moderation_metadata);
    }

    public function testModifyingALobbyByIdWorksAndCachesIt()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => ['id' => '1', 'application_id' => '2', 'metadata' => ['mode' => 'ranked'], 'members' => []]);

            $mock->lobbies->modifyLobby('1', ['metadata' => ['mode' => 'ranked']])
                ->then(function (Lobby $lobby) use ($mock, $driver) {
                    $this->assertSame('PATCH', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies/1', $driver->requests[0]['url']);
                    $this->assertEquals((object) ['mode' => 'ranked'], $lobby->metadata);

                    return $mock->lobbies->cacheGet('1')->then(fn ($cached) => $this->assertSame('1', $cached?->id));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testMembersAreAddedAtTheirOwnRoute()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => ['id' => '5', 'flags' => 0]);

            $lobby = $mock->getFactory()->part(Lobby::class, ['id' => '1'], true);

            $lobby->addMember('5', ['additional_name' => 'Sir Lancelot'])
                ->then(function (Member $member) use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    // No trailing slash: Discord treats that as a different route.
                    $this->assertStringEndsWith('/lobbies/1/members/5', $driver->requests[0]['url']);
                    $this->assertSame(['additional_name' => 'Sir Lancelot'], $driver->requests[0]['content']);
                    $this->assertSame('5', $member->id);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testBulkUpdatingSendsEveryMemberAndReturnsTheUpserted()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => [['id' => '5', 'flags' => 1]]);

            $mock->lobbies->bulkUpdateMembers('1', [
                $mock->getFactory()->part(Member::class, ['id' => '5', 'flags' => 1]),
                ['id' => '6', 'remove_member' => true],
            ])
                ->then(function ($members) use ($driver) {
                    $this->assertSame('POST', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies/1/members/bulk', $driver->requests[0]['url']);
                    $this->assertSame([['id' => '5', 'flags' => 1], ['id' => '6', 'remove_member' => true]], $driver->requests[0]['content']);
                    $this->assertCount(1, $members);
                    $this->assertInstanceOf(Member::class, $members->get('id', '5'));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnInviteIsCreatedForTheMember()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => ['code' => 'abc123']);

            $mock->lobbies->createInvite('1', '5')
                ->then(function (Invite $invite) use ($driver) {
                    $this->assertSame('POST', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies/1/members/5/invites', $driver->requests[0]['url']);
                    $this->assertSame('abc123', $invite->code);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testModerationMetadataIsPutOnTheMessage()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => null);

            $mock->lobbies->updateMessageModerationMetadata('1', '9', ['action' => 'hide', 'reason' => 'toxicity'])
                ->then(function () use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/lobbies/1/messages/9/moderation-metadata', $driver->requests[0]['url']);
                    $this->assertSame(['action' => 'hide', 'reason' => 'toxicity'], $driver->requests[0]['content']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testALobbyMessageModeratesItself()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->mockWith(fn () => null);

            $message = $mock->getFactory()->part(Message::class, ['id' => '9', 'lobby_id' => '1', 'channel_id' => '1'], true);

            $message->updateModerationMetadata(['action' => 'show'])
                ->then(function () use ($driver) {
                    $this->assertStringEndsWith('/lobbies/1/messages/9/moderation-metadata', $driver->requests[0]['url']);
                    $this->assertSame(['action' => 'show'], $driver->requests[0]['content']);
                })
                ->then($resolve, $resolve);
        });
    }

    /**
     * A client whose requests are answered by `$respond` rather than Discord.
     *
     * @return array{0: Discord, 1: object}
     */
    private function mockWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock, $driver];
    }
}
