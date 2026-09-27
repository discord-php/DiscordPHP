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
use Discord\OAuth2\AccessToken;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\IncidentsData;
use Discord\Parts\Guild\Role;
use Discord\Parts\Guild\ScheduledEvent;
use Discord\Parts\Guild\ScheduledEventException;
use Discord\Parts\User\Member;
use Discord\Repository\Guild\MemberRepository;

/**
 * Guild endpoints that Discord's OpenAPI description lists and DiscordPHP did not send before.
 */
final class GuildRequestsTest extends DiscordTestCase
{
    public function testChannelsAreMovedInOneRequest()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => null);
            $guild = $mock->getFactory()->part(Guild::class, ['id' => '10'], true);
            $general = $mock->getFactory()->part(Channel::class, ['id' => '101', 'guild_id' => '10', 'type' => Channel::TYPE_GUILD_TEXT, 'position' => 0], true);
            $guild->channels->pushItem($general);

            $guild->updateChannelPositions([1 => '100', 3 => $general])
                ->then(function ($result) use ($guild, $general, $driver) {
                    $this->assertSame($guild, $result);
                    $this->assertSame('PATCH', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/10/channels', $driver->requests[0]['url']);
                    $this->assertSame([['id' => '100', 'position' => 1], ['id' => '101', 'position' => 3]], $driver->requests[0]['content']);
                    $this->assertSame(3, $general->position, 'the cached channel moves before its CHANNEL_UPDATE arrives');
                })
                ->then($resolve, $resolve);
        });
    }

    public function testOnlyOneChannelMayChangeCategoryAtATime()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => null);
            $guild = $mock->getFactory()->part(Guild::class, ['id' => '10'], true);

            $guild->updateChannelPositions([
                ['id' => '100', 'parent_id' => '200', 'lock_permissions' => true],
                ['id' => '101', 'parent_id' => '200'],
            ])
                ->then(
                    fn () => $this->fail('Discord refuses two category changes in one request.'),
                    function (\Throwable $e) use ($driver) {
                        $this->assertInstanceOf(\InvalidArgumentException::class, $e);
                        $this->assertSame([], $driver->requests);
                    },
                )
                ->then($resolve, $resolve);
        });
    }

    public function testInvitesArePausedAndDirectMessagesResumed()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => ['invites_disabled_until' => '2026-09-26T12:00:00+00:00', 'dms_disabled_until' => null]);
            $guild = $mock->getFactory()->part(Guild::class, ['id' => '10'], true);

            $guild->updateIncidentActions(['invites_disabled_until' => Carbon::parse('2026-09-26T12:00:00+00:00'), 'dms_disabled_until' => null])
                ->then(function ($incidents) use ($guild, $driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/10/incident-actions', $driver->requests[0]['url']);
                    $this->assertSame(['invites_disabled_until' => '2026-09-26T12:00:00+00:00', 'dms_disabled_until' => null], $driver->requests[0]['content']);
                    $this->assertInstanceOf(IncidentsData::class, $incidents);
                    $this->assertSame('2026-09-26 12:00:00', $incidents->invites_disabled_until->format('Y-m-d H:i:s'));
                    $this->assertSame('2026-09-26 12:00:00', $guild->incidents_data->invites_disabled_until->format('Y-m-d H:i:s'), 'the guild keeps the new actions');
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAUserJoinsWithTheirOwnToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => ['user' => ['id' => '5', 'username' => 'five', 'discriminator' => '0'], 'roles' => ['30', '31'], 'nick' => 'Five', 'joined_at' => '2026-09-26T00:00:00+00:00']);
            $members = $mock->getFactory()->repository(MemberRepository::class, ['guild_id' => '10']);
            $role = $mock->getFactory()->part(Role::class, ['id' => '30', 'guild_id' => '10'], true);

            $members->add('5', new AccessToken('user-token'), ['nick' => 'Five', 'roles' => [$role, '31'], 'mute' => false, 'ignored' => true])
                ->then(function (Member $member) use ($driver) {
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/10/members/5', $driver->requests[0]['url']);
                    $this->assertSame(['access_token' => 'user-token', 'nick' => 'Five', 'roles' => ['30', '31'], 'mute' => false], $driver->requests[0]['content']);
                    $this->assertStringStartsWith('Bot ', $driver->requests[0]['headers']['Authorization'], 'the bot sends it, carrying the user\'s token');
                    $this->assertSame('5', $member->id);
                    $this->assertSame('Five', $member->nick);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAUserAlreadyInTheGuildIsFetched()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn (string $method) => 'PUT' === $method ? null : ['user' => ['id' => '5', 'username' => 'five', 'discriminator' => '0'], 'roles' => [], 'joined_at' => '2026-01-01T00:00:00+00:00']);
            $members = $mock->getFactory()->repository(MemberRepository::class, ['guild_id' => '10']);

            $members->add('5', 'user-token')
                ->then(function (Member $member) use ($driver) {
                    $this->assertSame(['PUT', 'GET'], array_column($driver->requests, 'method'));
                    $this->assertStringEndsWith('/guilds/10/members/5', $driver->requests[1]['url']);
                    $this->assertSame('5', $member->id);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testEveryExceptionOfAnEventIsKept()
    {
        [$mock] = $this->clientWith(fn () => null);
        $event = $this->recurringEvent($mock);

        $this->assertSame(['1', '2'], array_values(array_map(fn ($exception) => $exception->event_exception_id, $event->guild_scheduled_event_exceptions->toArray())));
    }

    public function testAnExceptionIsChangedAndDeleted()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn (string $method) => 'PATCH' === $method ? ['event_id' => '40', 'event_exception_id' => '2', 'guild_id' => '10', 'is_canceled' => true] : null);
            $event = $this->recurringEvent($mock);

            $event->updateException('2', ['is_canceled' => true, 'unknown' => 1], 'Snow day')
                ->then(function (ScheduledEventException $exception) use ($event, $driver) {
                    $this->assertSame('PATCH', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/10/scheduled-events/40/exceptions/2', $driver->requests[0]['url']);
                    $this->assertSame(['is_canceled' => true], $driver->requests[0]['content']);
                    $this->assertSame('Snow day', $driver->requests[0]['headers']['X-Audit-Log-Reason']);
                    $this->assertTrue($exception->is_canceled);
                    $this->assertTrue($event->guild_scheduled_event_exceptions->get('event_exception_id', '2')->is_canceled);

                    return $event->deleteException($exception);
                })
                ->then(function (?ScheduledEventException $deleted) use ($event, $driver) {
                    $this->assertSame('DELETE', $driver->requests[1]['method']);
                    $this->assertStringEndsWith('/guilds/10/scheduled-events/40/exceptions/2', $driver->requests[1]['url']);
                    $this->assertFalse($deleted->created);
                    $this->assertSame(['1'], array_values(array_map(fn ($exception) => $exception->event_exception_id, $event->guild_scheduled_event_exceptions->toArray())));
                })
                ->then($resolve, $resolve);
        });
    }

    private function recurringEvent(Discord $mock): ScheduledEvent
    {
        return $mock->getFactory()->part(ScheduledEvent::class, [
            'id' => '40',
            'guild_id' => '10',
            'scheduled_start_time' => '2026-09-01T18:00:00+00:00',
            'guild_scheduled_event_exceptions' => [
                ['event_id' => '40', 'event_exception_id' => '1', 'is_canceled' => false],
                ['event_id' => '40', 'event_exception_id' => '2', 'is_canceled' => false],
            ],
        ], true);
    }

    private function clientWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock, $driver];
    }
}
