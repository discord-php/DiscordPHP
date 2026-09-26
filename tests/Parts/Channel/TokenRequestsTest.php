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
use Discord\Exceptions\FileNotFoundException;
use Discord\OAuth2\AccessToken;
use Discord\Parts\Channel\Attachment;
use Discord\Parts\Channel\GroupDM;
use Discord\Parts\Channel\Webhook;
use Discord\Parts\Guild\Sticker;
use Discord\Parts\OAuth\Application;
use Discord\Parts\User\Client;
use Discord\Parts\User\User;

/**
 * Group DM recipients, webhooks by token, application attachments and stickers: endpoints Discord's
 * OpenAPI description lists and DiscordPHP did not send before.
 */
final class TokenRequestsTest extends DiscordTestCase
{
    public function testARecipientJoinsAndLeavesAGroupDM()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => null);
            $group = $mock->getFactory()->part(GroupDM::class, ['id' => '20', 'type' => 3, 'recipients' => [(object) ['id' => '2', 'username' => 'two', 'discriminator' => '0']]], true);
            $user = $mock->getFactory()->part(User::class, ['id' => '5', 'username' => 'five', 'discriminator' => '0'], true);

            $group->addRecipient($user, new AccessToken('user-token'), 'Five')
                ->then(function (GroupDM $result) use ($group, $driver) {
                    $this->assertSame($group, $result);
                    $this->assertSame('PUT', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/channels/20/recipients/5', $driver->requests[0]['url']);
                    $this->assertSame(['access_token' => 'user-token', 'nick' => 'Five'], $driver->requests[0]['content']);
                    $this->assertNotNull($group->recipients->get('id', '5'));

                    return $group->removeRecipient('2');
                })
                ->then(function () use ($group, $driver) {
                    $this->assertSame('DELETE', $driver->requests[1]['method']);
                    $this->assertStringEndsWith('/channels/20/recipients/2', $driver->requests[1]['url']);
                    $this->assertNull($group->recipients->get('id', '2'));
                    $this->assertNotNull($group->recipients->get('id', '5'));
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAWebhookIsReadChangedAndDeletedWithItsToken()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn (string $method) => 'DELETE' === $method ? null : ['id' => '60', 'type' => 1, 'name' => 'GET' === $method ? 'Hook' : 'Renamed', 'channel_id' => '70', 'token' => 'tok']);
            $webhook = $mock->getFactory()->part(Webhook::class, ['id' => '60', 'token' => 'tok'], true);

            $webhook->fetchByToken()
                ->then(function (Webhook $result) use ($webhook, $driver) {
                    $this->assertSame($webhook, $result);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/webhooks/60/tok', $driver->requests[0]['url']);
                    $this->assertSame('Hook', $webhook->name);

                    return $webhook->updateByToken(['name' => 'Renamed', 'channel_id' => '71']);
                })
                ->then(function () use ($webhook, $driver) {
                    $this->assertSame('PATCH', $driver->requests[1]['method']);
                    $this->assertSame(['name' => 'Renamed'], $driver->requests[1]['content'], 'a token cannot move the webhook');
                    $this->assertSame('Renamed', $webhook->name);

                    return $webhook->deleteByToken();
                })
                ->then(function () use ($webhook, $driver) {
                    $this->assertSame('DELETE', $driver->requests[2]['method']);
                    $this->assertStringEndsWith('/webhooks/60/tok', $driver->requests[2]['url']);
                    $this->assertFalse($webhook->created);
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAWebhookWithoutATokenIsRefused()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => null);
            $webhook = $mock->getFactory()->part(Webhook::class, ['id' => '60'], true);

            $webhook->deleteByToken()
                ->then(
                    fn () => $this->fail('There is no token to delete it with.'),
                    function (\Throwable $e) use ($driver) {
                        $this->assertInstanceOf(\LogicException::class, $e);
                        $this->assertSame([], $driver->requests);
                    },
                )
                ->then($resolve, $resolve);
        });
    }

    public function testAnApplicationUploadsAnAttachment()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => ['attachment' => ['id' => '80', 'filename' => 'moment.png', 'size' => 4, 'url' => 'https://cdn.discordapp.com/moment.png', 'proxy_url' => 'https://media.discordapp.net/moment.png']]);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);
            $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'moment.png';
            file_put_contents($file, "\x89PNG");

            $application->uploadAttachment($file)
                ->then(function (Attachment $attachment) use ($driver) {
                    $this->assertSame('POST', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/attachment', $driver->requests[0]['url']);
                    $this->assertStringStartsWith('multipart/form-data; boundary=', $driver->requests[0]['headers']['Content-Type']);
                    $this->assertStringContainsString('name=file; filename=moment.png', $driver->requests[0]['raw']);
                    $this->assertSame('80', $attachment->id);
                })
                ->then(fn () => unlink($file), fn ($e) => unlink($file) && throw $e)
                ->then($resolve, $resolve);
        });
    }

    public function testAMissingFileIsNotUploaded()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => null);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);

            $application->uploadAttachment(sys_get_temp_dir().DIRECTORY_SEPARATOR.'no-such-file.png')
                ->then(
                    fn () => $this->fail('There is no file to upload.'),
                    fn (\Throwable $e) => $this->assertInstanceOf(FileNotFoundException::class, $e),
                )
                ->then($resolve, $resolve);
        });
    }

    public function testAStickerIsFetchedWhenNoCachedPackHoldsIt()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn (string $method, string $url) => str_ends_with($url, '/applications/@me')
                ? ['id' => '7', 'name' => 'Bot']
                : ['id' => '3', 'name' => 'Wave', 'type' => 1, 'format_type' => 1, 'pack_id' => '9']);
            $client = $mock->getFactory()->part(Client::class, ['id' => '1', 'username' => 'bot', 'discriminator' => '0'], true);

            $client->getSticker('3')
                ->then(function (Sticker $sticker) use ($driver) {
                    $requested = array_values(array_filter($driver->requests, fn (array $request) => str_ends_with($request['url'], '/stickers/3')));
                    $this->assertCount(1, $requested);
                    $this->assertSame('GET', $requested[0]['method']);
                    $this->assertSame('Wave', $sticker->name);
                })
                ->then($resolve, $resolve);
        });
    }

    private function clientWith(callable $respond): array
    {
        $mock = getMockDiscord();
        $driver = getMockHttpDriver($respond);
        $mock->getHttpClient()->setDriver($driver);

        return [$mock, $driver];
    }
}
