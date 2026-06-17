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
use Discord\Http\Exceptions\NoPermissionsException;
use Discord\Parts\Guild\Guild;
use Discord\Parts\User\Member;
use Discord\Repository\GuildRepository;
use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;

use function React\Promise\resolve;

/**
 * Test stub that lets us instantiate Member without running the full Part
 * constructor (which requires a fully-wired Discord instance).
 */
class MemberTestStub extends Member
{
}

class MemberTestGuildRepositoryStub extends GuildRepository
{
    public ?PromiseInterface $_result = null;

    public function cacheGet($offset): PromiseInterface
    {
        /** @var PromiseInterface $result */
        $result = $this->_result;

        return $result;
    }
}

class MemberTestGuildStub extends Guild
{
    public ?\Discord\Parts\Permissions\RolePermission $_perms = null;

    public function getBotPermissions(): ?\Discord\Parts\Permissions\RolePermission
    {
        return $this->_perms;
    }
}

class MemberTestRolePermissionStub extends \Discord\Parts\Permissions\RolePermission
{
    public bool $_kickMembers = false;

    public function __get(string $key): mixed
    {
        return match ($key) {
            'kick_members' => $this->_kickMembers,
            default => false,
        };
    }
}

final class MemberTest extends TestCase
{
    /**
     * Regression test for Bug CRITIQUE #2 — `Member::kick()` previously
     * returned a raw `\RuntimeException` object when the member had no guild
     * instead of wrapping it in `reject(...)`.
     *
     * Consequence: a caller doing `$member->kick()->then(...)->catch(...)`
     * would never see the error because a resolved Promise was returned with
     * the exception object as its value.
     */
    public function testKickReturnsRejectedPromiseWhenMemberHasNoGuild(): void
    {
        $guilds = $this->instantiateWithoutConstructor(GuildRepository::class, []);
        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guilds : null);

        // Override guilds->cacheGet to resolve with null (member has no Guild)
        $guildsWithCacheGet = $this->createGuildRepositoryStub(resolve(null));

        $discord2 = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord2->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsWithCacheGet : null);

        $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_1']);
        $this->injectProperty($member, 'discord', $discord2);

        $result = $member->kick();

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertPromiseRejectsWith($result, \RuntimeException::class, 'Member has no Guild Part');
    }

    public function testKickRejectsWhenBotLacksKickMembersPermission(): void
    {
        $permsStub = $this->instantiateWithoutConstructor(MemberTestRolePermissionStub::class, []);
        $permsStub->_kickMembers = false;

        $guildStub = $this->createGuildStub('g_42', $permsStub);
        $guildsStub = $this->createGuildRepositoryStub(resolve($guildStub));

        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsStub : null);

        $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_42']);
        $this->injectProperty($member, 'discord', $discord);

        $result = $member->kick();

        $this->assertInstanceOf(PromiseInterface::class, $result);
        $this->assertPromiseRejectsWith($result, NoPermissionsException::class);
    }

    public function testKickAdversarialEmptyReasonDoesNotRegressNullGuildHandling(): void
    {
        // Empty string reason must not bypass the null-guild check.
        $guildsStub = $this->createGuildRepositoryStub(resolve(null));

        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsStub : null);

        $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_x']);
        $this->injectProperty($member, 'discord', $discord);

        $result = $member->kick('');

        $this->assertPromiseRejectsWith($result, \RuntimeException::class);
    }

    public function testKickAdversarialUnicodeReasonDoesNotRegressNullGuildHandling(): void
    {
        $guildsStub = $this->createGuildRepositoryStub(resolve(null));

        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsStub : null);

        $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_u']);
        $this->injectProperty($member, 'discord', $discord);

        $result = $member->kick("\u{1F480}\u{200B}"); // skull + zero-width space

        $this->assertPromiseRejectsWith($result, \RuntimeException::class);
    }

    /**
     * Adversarial: 100 concurrent kick() calls on members with no guild.
     * All must reject; none must leak an unhandled resolved promise carrying
     * a bare exception value.
     */
    public function testKickAdversarialConcurrentCallsAllRejectWhenGuildMissing(): void
    {
        $rejections = 0;

        for ($i = 0; $i < 100; $i++) {
            $guildsStub = $this->createGuildRepositoryStub(resolve(null));

            $discord = $this->getMockBuilder(Discord::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['__get'])
                ->getMock();
            $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsStub : null);

            $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_'.$i]);
            $this->injectProperty($member, 'discord', $discord);

            $result = $member->kick();

            $caught = null;
            $fulfilledWith = null;
            $result->then(
                function ($value) use (&$fulfilledWith): void {
                    $fulfilledWith = $value;
                },
                function ($reason) use (&$caught): void {
                    $caught = $reason;
                },
            );

            $this->assertNull($fulfilledWith, 'Promise should never resolve with a value');
            $this->assertInstanceOf(\RuntimeException::class, $caught);
            $rejections++;
        }

        $this->assertSame(100, $rejections);
    }

    /**
     * Guard against a future regression: `kick()` must never resolve to an
     * Exception object. The old bug returned `new \RuntimeException(...)` as
     * the resolved value, which is pathological because it is neither thrown
     * nor observable via `->catch(...)`.
     */
    public function testKickNeverResolvesWithExceptionValue(): void
    {
        $guildsStub = $this->createGuildRepositoryStub(resolve(null));

        $discord = $this->getMockBuilder(Discord::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__get'])
            ->getMock();
        $discord->method('__get')->willReturnCallback(fn (string $name) => $name === 'guilds' ? $guildsStub : null);

        $member = $this->instantiateWithoutConstructor(MemberTestStub::class, ['guild_id' => 'g_1']);
        $this->injectProperty($member, 'discord', $discord);

        $result = $member->kick();

        $resolved = null;
        $rejected = null;
        $result->then(
            function ($value) use (&$resolved): void {
                $resolved = $value;
            },
            function ($reason) use (&$rejected): void {
                $rejected = $reason;
            },
        );

        $this->assertNull($resolved, 'kick() must not resolve with a value when guild is missing.');
        $this->assertNotInstanceOf(
            \Throwable::class,
            $resolved,
            'kick() must not resolve with a Throwable value (old bug #2 symptom).'
        );
        $this->assertInstanceOf(\RuntimeException::class, $rejected);
    }

    private function createGuildStub(string $id, ?\Discord\Parts\Permissions\RolePermission $botPerms): Guild
    {
        $stub = $this->instantiateWithoutConstructor(MemberTestGuildStub::class, ['id' => $id]);
        $stub->_perms = $botPerms;

        return $stub;
    }

    private function createGuildRepositoryStub(PromiseInterface $cacheGetResult): GuildRepository
    {
        $stub = $this->instantiateWithoutConstructor(MemberTestGuildRepositoryStub::class, []);
        $stub->_result = $cacheGetResult;

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

    private function assertPromiseRejectsWith(PromiseInterface $promise, string $exceptionClass, ?string $messageFragment = null): void
    {
        $caught = null;
        $promise->then(
            fn () => null,
            function ($reason) use (&$caught): void {
                $caught = $reason;
            },
        );

        $this->assertInstanceOf($exceptionClass, $caught, "Promise should reject with $exceptionClass.");
        if ($messageFragment !== null) {
            $this->assertStringContainsString($messageFragment, $caught->getMessage());
        }
    }
}
