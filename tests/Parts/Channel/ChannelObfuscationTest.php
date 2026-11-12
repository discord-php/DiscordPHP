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
use Discord\Parts\Guild\Emoji;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Guild\Member\Member;
use Discord\Parts\Guild\Role\Role;
use Discord\Parts\User\Client;
use Discord\WebSockets\Events\ChannelUpdate;

use function Discord\promiseFromGenerator;

/**
 * Channels Discord sends obfuscated because the bot cannot view them, mandatory for bots from 16 November 2026.
 *
 * @link https://docs.discord.com/developers/resources/channel#channel-object-obfuscated-channels
 */
final class ChannelObfuscationTest extends DiscordTestCase
{
    private const GUILD = '10';
    private const BOT = '500';
    private const VIEW_CHANNEL = 1 << 10;
    private const SEND_MESSAGES = 1 << 11;
    private const MANAGE_GUILD_EXPRESSIONS = 1 << 30;
    private const CREATE_GUILD_EXPRESSIONS = 1 << 43;

    public function testTheFlagSaysWhetherAChannelIsObfuscated(): void
    {
        $factory = getMockDiscord()->getFactory();

        $this->assertTrue($factory->part(Channel::class, $this->obfuscated('100'), true)->isObfuscated());
        $this->assertFalse($factory->part(Channel::class, ['id' => '101', 'type' => Channel::TYPE_GUILD_TEXT, 'flags' => 0], true)->isObfuscated());
        $this->assertFalse($factory->part(Channel::class, ['id' => '102', 'type' => Channel::TYPE_GUILD_TEXT], true)->isObfuscated(), 'a channel without flags is not obfuscated');
    }

    public function testTheBotHasNoPermissionsInAChannelItCannotView(): void
    {
        [$mock, $guild] = $this->guildWithBot((string) (self::VIEW_CHANNEL | self::SEND_MESSAGES));
        $hidden = $mock->getFactory()->part(Channel::class, $this->obfuscated('100'), true);
        $guild->channels->pushItem($hidden);

        $botperms = $hidden->getBotPermissions();
        $this->assertNotNull($botperms, 'the bot still gets an answer, so permission checks fail early');
        $this->assertFalse($botperms->send_messages, 'without VIEW_CHANNEL every other channel permission is denied');
        $this->assertFalse($botperms->view_channel);

        $other = $this->member($mock, '600');
        $this->assertNull($other->getPermissions($hidden), 'the placeholder overwrites say nothing about other members');
        $this->assertTrue($other->getPermissions()->send_messages, 'guild-level permissions are unaffected');
    }

    public function testAnAdministratorStillHasEveryPermission(): void
    {
        [$mock, $guild] = $this->guildWithBot('8');
        $hidden = $mock->getFactory()->part(Channel::class, $this->obfuscated('100'), true);

        $this->assertTrue($this->member($mock, '600')->getPermissions($hidden)->send_messages);
    }

    public function testLosingViewClearsTheCachedChannelAndRegainingItRestoresIt()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $guild] = $this->guildWithBot((string) (self::VIEW_CHANNEL | self::SEND_MESSAGES));
            $full = [
                'id' => '100',
                'guild_id' => self::GUILD,
                'type' => Channel::TYPE_GUILD_TEXT,
                'name' => 'staff-only',
                'topic' => 'Moderation notes',
                'nsfw' => false,
                'position' => 4,
                'parent_id' => '90',
                'rate_limit_per_user' => 5,
                'flags' => 0,
                'permission_overwrites' => [
                    ['id' => self::GUILD, 'type' => 0, 'allow' => '0', 'deny' => (string) self::VIEW_CHANNEL],
                    ['id' => '700', 'type' => 0, 'allow' => (string) self::VIEW_CHANNEL, 'deny' => '0'],
                ],
            ];
            $channel = $mock->getFactory()->part(Channel::class, $full, true);
            $guild->channels->pushItem($channel);

            // The payload Discord sends leaves out the topic and rate limit entirely.
            $hidden = $this->obfuscated('100') + ['guild_id' => self::GUILD];

            promiseFromGenerator((new ChannelUpdate($mock))->handle((object) $hidden))
                ->then(function (array $updated) use ($mock, $channel, $full) {
                    [$new, $old] = $updated;
                    $this->assertSame($channel, $new, 'the cached channel object is updated in place');
                    $this->assertTrue($new->isObfuscated());
                    $this->assertSame('___hidden___', $new->name);
                    $this->assertNull($new->topic, 'a field the obfuscated payload leaves out is not carried over');
                    $this->assertNull($new->rate_limit_per_user);
                    $this->assertSame(4, $new->position);
                    $this->assertSame('90', $new->parent_id);
                    $this->assertSame(self::GUILD, $new->guild_id);
                    $this->assertSame([self::GUILD], array_map('strval', $new->overwrites->keys()), 'only the placeholder overwrite is left');

                    $this->assertFalse($old->isObfuscated());
                    $this->assertSame('Moderation notes', $old->topic, 'the listener still gets the channel as it was');

                    return promiseFromGenerator((new ChannelUpdate($mock))->handle((object) $full));
                })
                ->then(function (array $updated) use ($channel) {
                    [$new, $old] = $updated;
                    $this->assertSame($channel, $new);
                    $this->assertFalse($new->isObfuscated());
                    $this->assertSame('staff-only', $new->name);
                    $this->assertSame('Moderation notes', $new->topic);
                    $this->assertCount(2, $new->overwrites);
                    $this->assertTrue($old->isObfuscated());
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnEmojiNeedsOnlyTheCreatePermission()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $guild, $driver] = $this->guildWithBot((string) self::CREATE_GUILD_EXPRESSIONS, fn () => ['id' => '800', 'name' => 'wave', 'roles' => []]);

            $guild->createEmoji(['name' => 'wave', 'image' => 'data:image/png;base64,AAAA'])
                ->then(function (Emoji $emoji) use ($driver) {
                    $this->assertSame('800', $emoji->id);
                    $this->assertSame('POST', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/'.self::GUILD.'/emojis', $driver->requests[0]['url']);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testManagingExpressionsAloneDoesNotCreateEmojisOrStickers()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $guild, $driver] = $this->guildWithBot((string) self::MANAGE_GUILD_EXPRESSIONS, fn () => $this->fail('Discord needs CREATE_GUILD_EXPRESSIONS to create.'));

            $guild->createEmoji(['name' => 'wave', 'image' => 'data:image/png;base64,AAAA'])
                ->then(
                    fn () => $this->fail('The emoji should not be created.'),
                    fn (\Throwable $e) => $this->assertInstanceOf(NoPermissionsException::class, $e),
                )
                ->then(fn () => $guild->createSticker(['name' => 'wave', 'tags' => 'wave'], __FILE__))
                ->then(
                    fn () => $this->fail('The sticker should not be created.'),
                    fn (\Throwable $e) => $this->assertInstanceOf(NoPermissionsException::class, $e),
                )
                ->then(fn () => $this->assertSame([], $driver->requests))
                ->then($resolve, $resolve);
        });
    }

    /**
     * The payload Discord sends for a channel the bot cannot view.
     */
    private function obfuscated(string $id): array
    {
        return [
            'id' => $id,
            'type' => Channel::TYPE_GUILD_TEXT,
            'name' => '___hidden___',
            'position' => 4,
            'parent_id' => '90',
            'flags' => Channel::FLAG_CHANNEL_OBFUSCATED,
            'permission_overwrites' => [
                ['id' => self::GUILD, 'type' => 0, 'allow' => '0', 'deny' => (string) self::VIEW_CHANNEL],
            ],
            'guild_id' => self::GUILD,
        ];
    }

    /**
     * A client whose bot is a member of a guild where `@everyone` has the given permissions.
     *
     * @return array{0: Discord, 1: Guild, 2: object}
     */
    private function guildWithBot(string $everyonePermissions, ?callable $respond = null): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond ?? fn () => null);
        $mock->getHttpClient()->setDriver($driver);
        $mock->setClient($mock->getFactory()->part(Client::class, ['id' => self::BOT, 'username' => 'bot'], true));

        /** @var Guild */
        $guild = $mock->getFactory()->part(Guild::class, ['id' => self::GUILD, 'owner_id' => '1'], true);
        $mock->guilds->pushItem($guild);
        $guild->roles->pushItem($mock->getFactory()->part(Role::class, ['id' => self::GUILD, 'guild_id' => self::GUILD, 'permissions' => $everyonePermissions], true));

        $this->member($mock, self::BOT);

        return [$mock, $guild, $driver];
    }

    private function member(Discord $mock, string $id): Member
    {
        $guild = $mock->guilds->get('id', self::GUILD);
        /** @var Member */
        $member = $mock->getFactory()->part(Member::class, ['user' => (object) ['id' => $id, 'username' => "user$id"], 'roles' => [], 'guild_id' => self::GUILD], true);
        $guild->members->pushItem($member);

        return $member;
    }
}
