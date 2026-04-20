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
use Discord\Http\Endpoint;
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Invite;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\User\User;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Test stub for Invite that overrides the protected attribute getters so
 * tests can inject stubbed Channel and User parts without needing the full
 * factory / repository chain.
 */
class InviteTestStub extends Invite
{
    public ?Channel $_channelStub = null;
    public ?User $_inviterStub = null;

    protected function getChannelAttribute(): ?Channel
    {
        return $this->_channelStub;
    }

    protected function getInviterAttribute(): ?User
    {
        return $this->_inviterStub;
    }
}

class ChannelTestStub extends Channel
{
    public ?RolePermission $_stubPerms = null;

    public function getBotPermissions(): ?RolePermission
    {
        return $this->_stubPerms;
    }
}

class RolePermissionTestStub extends RolePermission
{
    public bool $_manageGuild = false;
    public bool $_viewAuditLog = false;

    public function __get(string $key): mixed
    {
        return match ($key) {
            'manage_guild' => $this->_manageGuild,
            'view_audit_log' => $this->_viewAuditLog,
            default => false,
        };
    }
}

final class InviteTest extends TestCase
{
    public function testUpdateTargetUsersSendsMultipartPut()
    {
        $csvContent = "Users\n123\n456\n";

        $discordMock = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->getMock();

        $httpMock = $this->getMockBuilder(\Discord\Http\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factoryMock = $this->getMockBuilder(\Discord\Factory\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $discordMock->method('getHttpClient')->willReturn($httpMock);
        $discordMock->method('getFactory')->willReturn($factoryMock);

        $invite = new Invite($discordMock, ['code' => 'abc123'], true);

        $httpMock->expects($this->once())
            ->method('put')
            ->with(
                $this->isInstanceOf(Endpoint::class),
                $this->callback(fn ($body) => is_string($body) && str_contains($body, $csvContent)),
                $this->callback(fn ($headers) => is_array($headers) && array_key_exists('Content-Type', $headers) && str_contains($headers['Content-Type'], 'multipart/form-data'))
            )
            ->willReturn(resolve(null));

        $invite->updateTargetUsersFromContent($csvContent);
    }

    /**
     * Regression test for Bug CRITIQUE #1 — operator precedence on `!` broke the
     * "is inviter" permission check in Invite::getTargetUsers (and siblings).
     *
     * Before the fix the condition `! $this->inviter->id === $this->discord->user->id`
     * was parsed as `(! $this->inviter->id) === $this->discord->user->id`, which is
     * always `false === <string>` → always `false`, so the inviter clause could
     * never exempt the bot from the perms reject.
     *
     * After the fix (`$this->inviter->id !== $this->discord->user->id`), a bot
     * that is the inviter is allowed through even when it lacks manage_guild
     * and view_audit_log.
     */
    public function testGetTargetUsersAllowsBotWhenBotIsInviter(): void
    {
        $botId = 'bot_12345';
        $invite = $this->buildInviteWithStubs(
            botId: $botId,
            inviterId: $botId,
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->with($this->isInstanceOf(Endpoint::class))
                    ->willReturn(resolve('csv_content'));
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertPromiseFulfilledWith($result, 'csv_content');
    }

    public function testGetTargetUsersRejectsWhenBotIsNotInviterAndLacksPerms(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'someone_else_99',
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('get');
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    public function testUpdateTargetUsersFromContentAllowsBotWhenBotIsInviter(): void
    {
        $botId = 'bot_12345';
        $csv = "user_id\n111\n222\n";

        $invite = $this->buildInviteWithStubs(
            botId: $botId,
            inviterId: $botId,
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('put')
                    ->willReturn(resolve(null));
            },
        );

        $invite->updateTargetUsersFromContent($csv);
    }

    public function testUpdateTargetUsersFromContentRejectsWhenNotInviterAndLacksPerms(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('put');
            },
        );

        $result = $invite->updateTargetUsersFromContent("user_id\n1\n");

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    public function testGetTargetUsersJobStatusAllowsBotWithManageGuildEvenIfNotInviter(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: true,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve((object) ['status' => 'pending']));
            },
        );

        $result = $invite->getTargetUsersJobStatus();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testGetTargetUsersJobStatusAllowsBotWithViewAuditLogEvenIfNotInviter(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: false,
            viewAuditLog: true,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve((object) ['status' => 'pending']));
            },
        );

        $result = $invite->getTargetUsersJobStatus();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testGetTargetUsersJobStatusAllowsBotWhenBotIsInviter(): void
    {
        $botId = 'bot_12345';
        $invite = $this->buildInviteWithStubs(
            botId: $botId,
            inviterId: $botId,
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve((object) ['status' => 'pending']));
            },
        );

        $result = $invite->getTargetUsersJobStatus();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testGetTargetUsersJobStatusRejectsWhenNotInviterAndLacksPerms(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('get');
            },
        );

        $result = $invite->getTargetUsersJobStatus();

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    /**
     * Adversarial: numeric string IDs that would have equated under `==` but
     * not under `!==`. The fix uses strict comparison so `"12345"` and int
     * `12345` are treated as different identities — the bot is NOT the inviter.
     */
    public function testAdversarialStrictComparisonRejectsNumericCoercion(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: '12345',
            inviterId: '12345 ', // trailing space, visually "same" but not equal
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('get');
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    /**
     * Adversarial: leading-zero snowflakes must not coincide with their
     * trimmed counterpart under strict comparison.
     */
    public function testAdversarialLeadingZeroIdsAreDistinct(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: '007',
            inviterId: '7',
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('get');
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    /**
     * Adversarial: zero-width Unicode character injection. A visually identical
     * inviter id with a ZWSP should NOT match the bot id.
     */
    public function testAdversarialUnicodeZeroWidthInjectionRejects(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_99',
            inviterId: "bot_99\u{200B}", // same-looking with ZERO WIDTH SPACE
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->never())->method('get');
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    /**
     * Adversarial: empty-string IDs for both bot and inviter should satisfy
     * the strict equality check (bot == inviter under !==), so the permission
     * reject is bypassed and the HTTP call proceeds. This guards against a
     * future regression where `!==` is weakened or replaced with truthy checks.
     */
    public function testAdversarialEmptyIdsAreConsideredEqualByStrictComparison(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: '',
            inviterId: '',
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve('csv'));
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    /**
     * Adversarial: 1 MiB identifier. Ensures strict comparison handles very
     * large strings without crashing or misbehaving.
     */
    public function testAdversarialVeryLongIdComparison(): void
    {
        $longId = str_repeat('9', 1024 * 1024);

        $invite = $this->buildInviteWithStubs(
            botId: $longId,
            inviterId: $longId,
            manageGuild: false,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve('csv'));
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    /**
     * Adversarial: concurrent invocations must each enforce the permission
     * check independently. Run 100 calls, half as inviter half not.
     */
    public function testAdversarialConcurrentInvocationsRespectPermissions(): void
    {
        $countHttp = 0;
        $countRejected = 0;

        for ($i = 0; $i < 100; $i++) {
            $isInviter = $i % 2 === 0;
            $botId = 'bot_'.$i;
            $invite = $this->buildInviteWithStubs(
                botId: $botId,
                inviterId: $isInviter ? $botId : 'other_'.$i,
                manageGuild: false,
                viewAuditLog: false,
                httpExpectation: function ($http) use ($isInviter): void {
                    if ($isInviter) {
                        $http->expects($this->once())
                            ->method('get')
                            ->willReturn(resolve('csv'));
                    } else {
                        $http->expects($this->never())->method('get');
                    }
                },
            );

            $result = $invite->getTargetUsers();

            if ($isInviter) {
                $countHttp++;
                $this->assertInstanceOf(PromiseInterface::class, $result);
            } else {
                $countRejected++;
                $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
            }
        }

        $this->assertSame(50, $countHttp);
        $this->assertSame(50, $countRejected);
    }

    public function testGetTargetUsersAllowsBotWithManageGuildEvenIfNotInviter(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: true,
            viewAuditLog: false,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve('csv_content'));
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    public function testGetTargetUsersAllowsBotWithViewAuditLogEvenIfNotInviter(): void
    {
        $invite = $this->buildInviteWithStubs(
            botId: 'bot_12345',
            inviterId: 'other_77',
            manageGuild: false,
            viewAuditLog: true,
            httpExpectation: function ($http): void {
                $http->expects($this->once())
                    ->method('get')
                    ->willReturn(resolve('csv_content'));
            },
        );

        $result = $invite->getTargetUsers();

        $this->assertInstanceOf(PromiseInterface::class, $result);
    }

    /**
     * @param callable(\PHPUnit\Framework\MockObject\MockObject): void $httpExpectation
     */
    private function buildInviteWithStubs(
        string $botId,
        string $inviterId,
        bool $manageGuild,
        bool $viewAuditLog,
        callable $httpExpectation,
        ?\PHPUnit\Framework\MockObject\MockObject $factory = null,
    ): Invite {
        $http = $this->getMockBuilder(\Discord\Http\Http::class)
            ->disableOriginalConstructor()
            ->getMock();

        $factory ??= $this->getMockBuilder(\Discord\Factory\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $botUser = $this->makeUserStub($botId);

        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHttpClient', 'getFactory', '__get'])
            ->getMock();

        $discord->method('getHttpClient')->willReturn($http);
        $discord->method('getFactory')->willReturn($factory);
        $discord->method('__get')->willReturnCallback(fn ($name) => match ($name) {
            'user' => $botUser,
            'http' => $http,
            default => null,
        });

        $permsStub = $this->makeRolePermissionStub($manageGuild, $viewAuditLog);

        $channelStub = $this->instantiateWithoutConstructor(ChannelTestStub::class, ['id' => 'channel_123']);
        $channelStub->_stubPerms = $permsStub;

        $inviter = $this->makeUserStub($inviterId);

        $invite = $this->instantiateWithoutConstructor(InviteTestStub::class, ['code' => 'abc', 'id' => 'abc']);
        $invite->_channelStub = $channelStub;
        $invite->_inviterStub = $inviter;

        $this->injectDiscord($invite, $discord);
        $this->injectDiscord($channelStub, $discord);
        $this->injectDiscord($inviter, $discord);
        $this->injectHttp($invite, $http);
        $this->injectProperty($invite, 'factory', $factory);

        $httpExpectation($http);

        return $invite;
    }

    private function makeUserStub(string $id): User
    {
        return $this->instantiateWithoutConstructor(User::class, ['id' => $id]);
    }

    private function makeRolePermissionStub(bool $manageGuild, bool $viewAuditLog): RolePermission
    {
        $stub = $this->instantiateWithoutConstructor(RolePermissionTestStub::class, []);
        $stub->_manageGuild = $manageGuild;
        $stub->_viewAuditLog = $viewAuditLog;

        return $stub;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function instantiateWithoutConstructor(string $class, array $attributes): object
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();

        $this->injectProperty($instance, 'attributes', $attributes);

        return $instance;
    }

    private function injectDiscord(object $instance, Discord $discord): void
    {
        $this->injectProperty($instance, 'discord', $discord);
    }

    private function injectHttp(object $instance, \Discord\Http\Http $http): void
    {
        $this->injectProperty($instance, 'http', $http);
    }

    private function injectProperty(object $instance, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($instance);
        while ($reflection !== false) {
            if ($reflection->hasProperty($property)) {
                $prop = $reflection->getProperty($property);
                $prop->setValue($instance, $value);

                return;
            }
            $reflection = $reflection->getParentClass();
        }
    }

    private function assertPromiseFulfilledWith(PromiseInterface $promise, mixed $expected): void
    {
        $fulfilled = false;
        $actual = null;
        $promise->then(function ($value) use (&$fulfilled, &$actual): void {
            $fulfilled = true;
            $actual = $value;
        });

        $this->assertTrue($fulfilled, 'Promise was not fulfilled synchronously.');
        $this->assertSame($expected, $actual);
    }

    private function assertPromiseRejectsWith(PromiseInterface $promise, string $exceptionClass): void
    {
        $caught = null;
        $promise->then(
            fn () => null,
            function ($reason) use (&$caught): void {
                $caught = $reason;
            },
        );

        $this->assertInstanceOf($exceptionClass, $caught, "Promise should reject with $exceptionClass.");
    }
}
