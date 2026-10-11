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

use Discord\Builders\ApplicationIdentityProfileBuilder;
use Discord\Discord;
use Discord\Parts\Application\Application;
use Discord\Parts\Application\Identity\ApplicationIdentityProfile;
use Discord\Parts\Application\Identity\DynamicField;
use PHPUnit\Framework\Attributes\DataProvider;

final class ApplicationIdentityProfileBuilderTest extends DiscordTestCase
{
    public function testFromPartRoundTripAndWritableFields(): void
    {
        $raw = [
            'username' => 'player',
            'metadata' => ['response_only' => true],
            'data' => [
                'primary' => ['rank_name' => 'Gold', 'total_wins' => 0, 'playtime_hours' => 1.25],
                'dynamic' => [
                    ['type' => DynamicField::TYPE_STRING, 'name' => 'title', 'value' => 'Champion'],
                    ['type' => DynamicField::TYPE_NUMBER, 'name' => 'ratio', 'value' => 1.5],
                    ['type' => DynamicField::TYPE_MEDIA, 'name' => 'portrait', 'value' => ['url' => 'https://example.com/image.png']],
                ],
            ],
        ];
        $profile = getMockDiscord()->getFactory()->part(ApplicationIdentityProfile::class, $raw, true);
        // Read every typed level before copying to prove hydration does not change authoring.
        $this->assertSame('https://example.com/image.png', $profile->data->dynamic->last()->value->url);
        $builder = ApplicationIdentityProfileBuilder::fromPart($profile);
        unset($raw['metadata']);
        $this->assertSame($raw, json_decode(json_encode($builder), true));
        $this->assertSame($raw['data'], ApplicationIdentityProfileBuilder::new()->setData($profile->data)->getData());
        $this->assertSame(['username' => 'player'], $builder->omitData()->jsonSerialize());
    }

    public function testAbsentNullAndEmptyObjects(): void
    {
        foreach ([[], ['username' => null, 'data' => null], ['data' => ['dynamic' => []]]] as $raw) {
            $profile = getMockDiscord()->getFactory()->part(ApplicationIdentityProfile::class, $raw);
            $this->assertSame($raw, json_decode(json_encode(ApplicationIdentityProfileBuilder::fromPart($profile)), true));
        }
        $this->assertSame('{"data":{}}', json_encode(ApplicationIdentityProfileBuilder::new()->setData([])));
        $this->assertSame('{"data":{"primary":{},"dynamic":[]}}', json_encode(ApplicationIdentityProfileBuilder::new()->setData(['primary' => [], 'dynamic' => []])));
    }

    public function testDocumentedLengthAndCountEdges(): void
    {
        $data = ['primary' => ['rank_name' => str_repeat('é', 100)], 'dynamic' => array_fill(0, 30, [
            'type' => 1, 'name' => str_repeat('a', 100), 'value' => str_repeat('b', 100),
        ])];
        $builder = ApplicationIdentityProfileBuilder::new()->setUsername(str_repeat('é', 1024))->setData($data);
        $this->assertSame($data, $builder->getData());
        $this->assertSame(1024, \Discord\poly_strlen($builder->getUsername()));
    }

    public function testUsernameLimit(): void
    {
        $this->expectException(LengthException::class);
        ApplicationIdentityProfileBuilder::new()->setUsername(str_repeat('a', 1025));
    }

    #[DataProvider('invalidData')]
    public function testInvalidDataIsRejected(array $data, string $exception): void
    {
        $this->expectException($exception);
        ApplicationIdentityProfileBuilder::new()->setData($data);
    }

    public static function invalidData(): array
    {
        return [
            'primary length' => [['primary' => ['season' => str_repeat('a', 101)]], LengthException::class],
            'name length' => [['dynamic' => [['type' => 1, 'name' => str_repeat('a', 101), 'value' => 'ok']]], LengthException::class],
            'value length' => [['dynamic' => [['type' => 1, 'name' => 'n', 'value' => str_repeat('a', 101)]]], LengthException::class],
            'count' => [['dynamic' => array_fill(0, 31, ['type' => 2, 'name' => 'wins', 'value' => 0])], LengthException::class],
            'integer' => [['primary' => ['total_wins' => 1.5]], InvalidArgumentException::class],
            'number' => [['primary' => ['playtime_hours' => '1.5']], InvalidArgumentException::class],
            'string' => [['dynamic' => [['type' => 1, 'name' => 'n', 'value' => 5]]], InvalidArgumentException::class],
            'missing value' => [['dynamic' => [['type' => 2, 'name' => 'n']]], InvalidArgumentException::class],
            'unknown type' => [['dynamic' => [['type' => 99, 'name' => 'n', 'value' => 5]]], InvalidArgumentException::class],
            'string type' => [['dynamic' => [['type' => '2', 'name' => 'n', 'value' => 5]]], InvalidArgumentException::class],
            'media' => [['dynamic' => [['type' => 3, 'name' => 'n', 'value' => ['url' => 'file:///image']]]], InvalidArgumentException::class],
            'primary media' => [['primary' => ['rank_image' => ['url' => 'invalid']]], InvalidArgumentException::class],
            'unknown field' => [['primary' => ['typo' => 1]], InvalidArgumentException::class],
            'null primary' => [['primary' => null], InvalidArgumentException::class],
            'null dynamic' => [['dynamic' => null], InvalidArgumentException::class],
            'keyed dynamic' => [['dynamic' => ['wins' => ['type' => 2, 'name' => 'wins', 'value' => 1]]], InvalidArgumentException::class],
        ];
    }

    public function testSerializedSizeUsesHttpEncodingAndExcludesUsername(): void
    {
        $data = ['dynamic' => [['type' => 3, 'name' => 'image', 'value' => ['url' => 'https://example.com/'.str_repeat('a', 9000)]]]];
        $builder = ApplicationIdentityProfileBuilder::new()->setUsername(str_repeat('a', 1024))->setData($data);
        $this->assertGreaterThan(10000, strlen(json_encode($builder)));
        $this->expectException(LengthException::class);
        // Slashes each consume two encoded bytes in the locked HTTP client.
        $builder->setData(['dynamic' => [['type' => 3, 'name' => 'image', 'value' => ['url' => 'https://example.com/'.str_repeat('/', 5200)]]]]);
    }

    public function testSerializedDataSizeBoundary(): void
    {
        $data = ['dynamic' => [['type' => 3, 'name' => 'image', 'value' => ['url' => 'https://example.com/']]]];
        $padding = 10240 - strlen(json_encode($data));
        $data['dynamic'][0]['value']['url'] .= str_repeat('a', $padding);
        $this->assertSame(10240, strlen(json_encode($data)));
        $builder = ApplicationIdentityProfileBuilder::new()->setData($data);
        $this->assertSame($data, $builder->getData());
        $data['dynamic'][0]['value']['url'] .= 'a';
        $this->expectException(LengthException::class);
        $builder->setData($data);
    }

    public function testNestedPartsCanBeAuthoredWithoutNullOptionalStats(): void
    {
        $mock = getMockDiscord();
        $primary = $mock->getFactory()->part(\Discord\Parts\Application\Identity\PrimaryProfileData::class, ['total_wins' => 0]);
        $field = $mock->getFactory()->part(\Discord\Parts\Application\Identity\DynamicMediaField::class, [
            'type' => 3, 'name' => 'rank', 'value' => ['url' => 'https://example.com/rank.png'],
        ]);
        $data = $mock->getFactory()->part(\Discord\Parts\Application\Identity\ProfileData::class, ['primary' => $primary, 'dynamic' => [$field]]);
        $builder = ApplicationIdentityProfileBuilder::new()->setData($data);
        $this->assertSame(['primary' => ['total_wins' => 0], 'dynamic' => [
            ['type' => 3, 'name' => 'rank', 'value' => ['url' => 'https://example.com/rank.png']],
        ]], $builder->getData());
    }
    public function testFetchedProfileCanBeCopiedAndPublished()
    {
        return wait(function (Discord $discord, $resolve) {
            $snapshot = ['username' => 'player', 'data' => [
                'primary' => ['rank_name' => 'Gold', 'total_wins' => 0],
                'dynamic' => [['type' => 3, 'name' => 'rank', 'value' => ['url' => 'https://example.com/rank.png']]],
            ]];
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn ($method) => $method === 'GET' ? $snapshot : null);
            $mock->getHttpClient()->setDriver($driver);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);
            $application->identities->getProfile('5', 'abc')
                ->then(function (ApplicationIdentityProfile $profile) use ($application) {
                    // HTTP decodes nested data as stdClass; reading typed data keeps it raw.
                    $this->assertInstanceOf(stdClass::class, $profile->getRawAttributes()['data']);
                    $this->assertSame('Gold', $profile->data->primary->rank_name);

                    return ApplicationIdentityProfileBuilder::fromPart($profile)->publish($application->identities, '5', 'abc');
                })
                ->then(function () use ($driver, $snapshot) {
                    $this->assertSame('PATCH', $driver->requests[1]['method']);
                    $this->assertSame($snapshot, $driver->requests[1]['content']);
                })
                ->then($resolve, $resolve);
        });
    }
    public function testPublishRecordsReplacementAndUsernameOnlyRequests()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $driver = getMockHttpDriver(fn () => null);
            $mock->getHttpClient()->setDriver($driver);
            $application = $mock->getFactory()->part(Application::class, ['id' => '7'], true);
            $builder = ApplicationIdentityProfileBuilder::new()->setData(['primary' => ['rank_name' => 'Gold']]);
            $builder->publish($application->identities, '5', 'account-1')
                ->then(function () use ($builder, $application, $driver) {
                    $this->assertSame('PATCH', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/applications/7/users/5/identities/account-1/profile', $driver->requests[0]['url']);
                    $this->assertSame(['data' => ['primary' => ['rank_name' => 'Gold']]], $driver->requests[0]['content']);

                    return $builder->omitData()->setUsername('new')->publish($application->identities, '5', 'account-1');
                })
                ->then(function () use ($application, $driver) {
                    $this->assertSame(['username' => 'new'], $driver->requests[1]['content']);

                    return $application->identities->updateProfile('5', 'abc', ['data' => null]);
                })
                ->then(function () use ($application, $driver) {
                    $this->assertSame(['data' => null], $driver->requests[2]['content']);

                    return ApplicationIdentityProfileBuilder::new()->setData([])->publish($application->identities, '5', 'abc');
                })
                ->then(fn () => $this->assertSame('{"data":{}}', $driver->requests[3]['raw']))
                ->then($resolve, $resolve);
        });
    }
}
