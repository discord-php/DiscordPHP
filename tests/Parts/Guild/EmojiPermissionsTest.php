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
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Member\Member;
use Discord\Parts\Guild\Role\Role;
use Discord\Parts\User\Client;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The permissions Emoji::save() asks of the bot before it sends a request.
 *
 * @link https://docs.discord.com/developers/resources/emoji#create-guild-emoji
 * @link https://docs.discord.com/developers/resources/emoji#modify-guild-emoji
 */
final class EmojiPermissionsTest extends DiscordTestCase
{
    private const GUILD = '10';
    private const BOT = '500';
    private const OTHER_USER = '42';
    private const EMOJI = '800';
    private const MANAGE_GUILD_EXPRESSIONS = 1 << 30;
    private const CREATE_GUILD_EXPRESSIONS = 1 << 43;

    public static function edits(): array
    {
        return [
            "another user's emoji with Manage Expressions" => [self::OTHER_USER, self::MANAGE_GUILD_EXPRESSIONS, true],
            "another user's emoji with only Create Expressions" => [self::OTHER_USER, self::CREATE_GUILD_EXPRESSIONS, false],
            "another user's emoji with neither permission" => [self::OTHER_USER, 0, false],
            "the bot's own emoji with only Create Expressions" => [self::BOT, self::CREATE_GUILD_EXPRESSIONS, true],
            "the bot's own emoji with only Manage Expressions" => [self::BOT, self::MANAGE_GUILD_EXPRESSIONS, true],
            "the bot's own emoji with neither permission" => [self::BOT, 0, false],
            'an emoji without a creator with only Create Expressions' => [null, self::CREATE_GUILD_EXPRESSIONS, true],
            'an emoji without a creator with only Manage Expressions' => [null, self::MANAGE_GUILD_EXPRESSIONS, true],
        ];
    }

    #[DataProvider('edits')]
    public function testEditingAnEmojiChecksThePermissionDiscordRequires(?string $creator, int $permissions, bool $allowed)
    {
        return wait(function (Discord $discord, $resolve) use ($creator, $permissions, $allowed) {
            [$mock, $guild, $driver] = $this->guildWithBot((string) $permissions, fn () => ['id' => self::EMOJI, 'name' => 'renamed', 'roles' => []]);

            $attributes = ['id' => self::EMOJI, 'name' => 'wave', 'roles' => [], 'guild_id' => self::GUILD];
            if ($creator !== null) {
                $attributes['user'] = (object) ['id' => $creator, 'username' => "user$creator"];
            }

            /** @var Emoji */
            $emoji = $mock->getFactory()->part(Emoji::class, $attributes, true);
            $emoji->name = 'renamed';

            $emoji->save()
                ->then(
                    function () use ($allowed, $driver) {
                        $this->assertTrue($allowed, 'The edit should have been refused.');
                        $this->assertCount(1, $driver->requests);
                        $this->assertSame('PATCH', $driver->requests[0]['method']);
                        $this->assertStringEndsWith('/guilds/'.self::GUILD.'/emojis/'.self::EMOJI, $driver->requests[0]['url']);
                    },
                    function (\Throwable $e) use ($allowed, $driver) {
                        $this->assertFalse($allowed, 'The edit should have been sent: '.$e->getMessage());
                        $this->assertInstanceOf(NoPermissionsException::class, $e);
                        $this->assertSame([], $driver->requests);
                    },
                )
                ->then($resolve, $resolve);
        });
    }

    public static function creations(): array
    {
        return [
            'Create Expressions' => [self::CREATE_GUILD_EXPRESSIONS, true],
            'only Manage Expressions' => [self::MANAGE_GUILD_EXPRESSIONS, false],
        ];
    }

    #[DataProvider('creations')]
    public function testSavingANewEmojiNeedsTheCreatePermission(int $permissions, bool $allowed)
    {
        return wait(function (Discord $discord, $resolve) use ($permissions, $allowed) {
            [$mock, $guild, $driver] = $this->guildWithBot((string) $permissions, fn () => ['id' => self::EMOJI, 'name' => 'wave', 'roles' => []]);

            /** @var Emoji */
            $emoji = $mock->getFactory()->part(Emoji::class, ['name' => 'wave', 'guild_id' => self::GUILD]);

            $emoji->save()
                ->then(
                    function () use ($allowed, $driver) {
                        $this->assertTrue($allowed, 'The emoji should not have been created.');
                        $this->assertSame('POST', $driver->requests[0]['method']);
                        $this->assertStringEndsWith('/guilds/'.self::GUILD.'/emojis', $driver->requests[0]['url']);
                    },
                    function (\Throwable $e) use ($allowed, $driver) {
                        $this->assertFalse($allowed, 'The emoji should have been created: '.$e->getMessage());
                        $this->assertInstanceOf(NoPermissionsException::class, $e);
                        $this->assertSame([], $driver->requests);
                    },
                )
                ->then($resolve, $resolve);
        });
    }

    /**
     * A client whose bot is a member of a guild where `@everyone` has the given permissions.
     *
     * @return array{0: Discord, 1: Guild, 2: object}
     */
    private function guildWithBot(string $everyonePermissions, callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);
        $mock->setClient($mock->getFactory()->part(Client::class, ['id' => self::BOT, 'username' => 'bot'], true));

        /** @var Guild */
        $guild = $mock->getFactory()->part(Guild::class, ['id' => self::GUILD, 'owner_id' => '1'], true);
        $mock->guilds->pushItem($guild);
        $guild->roles->pushItem($mock->getFactory()->part(Role::class, ['id' => self::GUILD, 'guild_id' => self::GUILD, 'permissions' => $everyonePermissions], true));

        /** @var Member */
        $member = $mock->getFactory()->part(Member::class, ['user' => (object) ['id' => self::BOT, 'username' => 'bot'], 'roles' => [], 'guild_id' => self::GUILD], true);
        $guild->members->pushItem($member);

        return [$mock, $guild, $driver];
    }
}
