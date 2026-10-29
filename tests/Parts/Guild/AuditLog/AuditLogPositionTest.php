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
use Discord\Parts\Guild\AuditLog\AuditLog;
use Discord\Parts\Guild\AuditLog\Entry;
use Discord\Parts\Guild\Guild;

final class AuditLogPositionTest extends DiscordTestCase
{
    public function testPositionEventsAreSearchableUsingDiscordsActionTypes(): void
    {
        $log = getMockDiscord()->getFactory()->part(AuditLog::class, ['audit_log_entries' => [
            ['id' => '100', 'action_type' => 33, 'options' => ['count' => '2']],
            ['id' => '101', 'action_type' => 16, 'options' => ['count' => '3']],
            ['id' => '102', 'action_type' => 31],
            ['id' => '103', 'action_type' => 35],
        ]], true);

        $roles = $log->searchByType(Entry::ROLE_POSITION_UPDATE);
        $this->assertCount(1, $roles);
        $this->assertSame('100', $roles->first()->id);
        $this->assertSame(33, $roles->first()->action_type);
        $this->assertSame('2', $roles->first()->options->count);
        $this->assertTrue($roles->first()->created);

        $channels = $log->searchByType(Entry::CHANNEL_POSITION_UPDATE);
        $this->assertCount(1, $channels);
        $this->assertSame('101', $channels->first()->id);
        $this->assertSame(16, $channels->first()->action_type);
        $this->assertSame('3', $channels->first()->options->count);
    }

    public function testIncorrectRolePositionActionTypeIsRejected(): void
    {
        $log = getMockDiscord()->getFactory()->part(AuditLog::class, ['audit_log_entries' => []], true);
        $this->expectException(\InvalidArgumentException::class);
        $log->searchByType(35);
    }

    public function testRolePositionFilterSendsTheDocumentedActionType()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => ['audit_log_entries' => [['id' => '100', 'action_type' => 33]]]);
            $mock->getHttpClient()->setDriver($driver);
            $guild = $mock->getFactory()->part(Guild::class, ['id' => '10'], true);

            $guild->getAuditLog(['action_type' => Entry::ROLE_POSITION_UPDATE])->then(function () use ($driver) {
                $this->assertCount(1, $driver->requests);
                $this->assertSame('GET', $driver->requests[0]['method']);
                $this->assertStringEndsWith('/guilds/10/audit-logs?action_type=33', $driver->requests[0]['url']);
            })->then($resolve, $resolve);
        });
    }
}
