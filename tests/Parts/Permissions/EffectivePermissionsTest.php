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
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Thread\Thread;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Member\Member;
use Discord\Parts\Guild\Role\Role;
use Discord\Parts\Permissions\RolePermission;
use Discord\Parts\User\User;
use PHPUnit\Framework\Attributes\DataProvider;
use React\Http\Message\Response;

final class EffectivePermissionsTest extends DiscordTestCase
{
    public static function requests(): array
    {
        return [
            'guild member ID' => [Guild::class, 'fetchMemberPermissions', '20', true, '/guilds/10/permissions/member/20'],
            'guild member Part' => [Guild::class, 'fetchMemberPermissions', Member::class, true, '/guilds/10/permissions/member/20'],
            'guild user Part' => [Guild::class, 'fetchMemberPermissions', User::class, true, '/guilds/10/permissions/member/20'],
            'guild role ID' => [Guild::class, 'fetchRolePermissions', '20', true, '/guilds/10/permissions/role/20'],
            'guild role Part' => [Guild::class, 'fetchRolePermissions', Role::class, true, '/guilds/10/permissions/role/20'],
            'channel member default' => [Channel::class, 'fetchMemberPermissions', '20', true, '/channels/10/permissions/member/20?implicit=true'],
            'channel member Part' => [Channel::class, 'fetchMemberPermissions', Member::class, false, '/channels/10/permissions/member/20?implicit=false'],
            'channel user Part' => [Channel::class, 'fetchMemberPermissions', User::class, true, '/channels/10/permissions/member/20?implicit=true'],
            'channel role default' => [Channel::class, 'fetchRolePermissions', '20', true, '/channels/10/permissions/role/20?implicit=true'],
            'channel role Part' => [Channel::class, 'fetchRolePermissions', Role::class, false, '/channels/10/permissions/role/20?implicit=false'],
            'thread member' => [Thread::class, 'fetchMemberPermissions', '20', false, '/channels/10/permissions/member/20?implicit=false'],
            'thread role' => [Thread::class, 'fetchRolePermissions', '20', true, '/channels/10/permissions/role/20?implicit=true'],
        ];
    }

    #[DataProvider('requests')]
    public function testFetchesTypedPermissionsWithoutRequiringOrChangingCaches(string $resourceClass, string $method, string $targetClassOrId, bool $implicit, string $path)
    {
        return wait(function (Discord $discord, $resolve) use ($resourceClass, $method, $targetClassOrId, $implicit, $path) {
            $mock = getMockDiscord();
            // VIEW_CHANNEL, ADMINISTRATOR and BYPASS_SLOWMODE: exercise the string bitfield
            // and retain guild flags even when the result comes from a channel endpoint.
            $bitwise = '4503599627371528';
            $driver = getMockHttpDriver(fn () => ['permissions' => $bitwise]);
            $mock->getHttpClient()->setDriver($driver);
            $resource = $mock->getFactory()->part($resourceClass, ['id' => '10', 'guild_id' => '1', 'type' => Channel::TYPE_GUILD_TEXT], true);
            $target = $targetClassOrId === '20' ? '20' : $mock->getFactory()->part($targetClassOrId, [
                'id' => '20', 'guild_id' => '1', 'user' => ['id' => '20'], 'permissions' => '2048',
            ], true);
            $before = is_string($target) ? null : $target->getRawAttributes();

            $promise = $implicit ? $resource->$method($target) : $resource->$method($target, false);
            $promise->then(function ($permissions) use ($mock, $resource, $target, $before, $driver, $bitwise, $path) {
                $this->assertCount(1, $driver->requests);
                $request = $driver->requests[0];
                $this->assertSame('GET', $request['method']);
                $this->assertStringEndsWith($path, $request['url']);
                $this->assertNull($request['content']);
                $this->assertStringStartsWith('Bot ', $request['headers']['Authorization']);
                $this->assertInstanceOf(RolePermission::class, $permissions);
                $this->assertSame($bitwise, (string) $permissions);
                $this->assertTrue($permissions->administrator);
                $this->assertTrue($permissions->view_channel);
                $this->assertTrue($permissions->bypass_slowmode);
                $this->assertFalse($permissions->send_messages);
                $this->assertTrue($permissions->created);
                if (! is_string($target)) {
                    $this->assertSame($before, $target->getRawAttributes());
                }
                if ($target instanceof Role || $target instanceof Member) {
                    $this->assertSame('2048', (string) $target->permissions);
                }
                $this->assertCount(0, $mock->guilds);
                if ($resource instanceof Guild) {
                    $this->assertCount(0, $resource->members);
                    $this->assertCount(0, $resource->roles);
                }
            })->then($resolve, $resolve);
        });
    }

    public static function privateChannels(): array
    {
        return [
            [Channel::TYPE_DM, 'fetchMemberPermissions'],
            [Channel::TYPE_GROUP_DM, 'fetchMemberPermissions'],
            [Channel::TYPE_DM, 'fetchRolePermissions'],
            [Channel::TYPE_GROUP_DM, 'fetchRolePermissions'],
        ];
    }

    #[DataProvider('privateChannels')]
    public function testPrivateChannelsRejectWithoutSendingARequest(int $type, string $method)
    {
        return wait(function (Discord $discord, $resolve) use ($type, $method) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => null);
            $mock->getHttpClient()->setDriver($driver);
            $channel = $mock->getFactory()->part(Channel::class, ['id' => '10', 'type' => $type], true);
            $channel->$method('20')->then(
                fn () => $this->fail('Guild permissions cannot be fetched in DMs.'),
                function ($error) use ($driver) {
                    $this->assertInstanceOf(\BadMethodCallException::class, $error);
                    $this->assertSame([], $driver->requests);
                }
            )->then($resolve, $resolve);
        });
    }

    public static function endpoints(): array
    {
        return [
            [Guild::class, 'fetchMemberPermissions'],
            [Guild::class, 'fetchRolePermissions'],
            [Channel::class, 'fetchMemberPermissions'],
            [Channel::class, 'fetchRolePermissions'],
        ];
    }

    #[DataProvider('endpoints')]
    public function testZeroPermissionsResolveNormally(string $resourceClass, string $method)
    {
        return wait(function (Discord $discord, $resolve) use ($resourceClass, $method) {
            $mock = getMockDiscord();
            $mock->getHttpClient()->setDriver(getMockHttpDriver(fn () => ['permissions' => '0']));
            $resource = $mock->getFactory()->part($resourceClass, ['id' => '10', 'type' => Channel::TYPE_GUILD_TEXT], true);
            $resource->$method('20')->then(function (RolePermission $permissions) {
                $this->assertSame('0', (string) $permissions);
                $this->assertFalse($permissions->administrator);
                $this->assertFalse($permissions->view_channel);
            })->then($resolve, $resolve);
        });
    }

    #[DataProvider('endpoints')]
    public function testApiErrorsRejectInsteadOfReturningEmptyPermissions(string $resourceClass, string $method)
    {
        return wait(function (Discord $discord, $resolve) use ($resourceClass, $method) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => new Response(403, ['Content-Type' => 'application/json'], '{"code":50001,"message":"Missing Access"}'));
            $mock->getHttpClient()->setDriver($driver);
            $resource = $mock->getFactory()->part($resourceClass, ['id' => '10', 'type' => Channel::TYPE_GUILD_TEXT], true);
            $resource->$method('20')->then(
                fn () => $this->fail('An API error must reject.'),
                function ($error) use ($driver) {
                    $this->assertInstanceOf(NoPermissionsException::class, $error);
                    $this->assertCount(1, $driver->requests);
                }
            )->then($resolve, $resolve);
        });
    }
}
