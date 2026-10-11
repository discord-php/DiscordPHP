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

use Discord\Parts\Application\Identity\ApplicationIdentityProfile;
use Discord\Parts\Application\Identity\DynamicField;
use Discord\Parts\Application\Identity\DynamicMediaField;
use Discord\Parts\Application\Identity\DynamicNumberField;
use Discord\Parts\Application\Identity\DynamicStringField;
use Discord\Parts\Application\Identity\PrimaryProfileData;
use Discord\Parts\Application\Identity\ProfileData;
use Discord\Parts\Application\Identity\ProfileMedia;

final class ApplicationIdentityProfileTest extends DiscordTestCase
{
    public function testTypedReadsPreserveRawPayloadAndJson(): void
    {
        $raw = [
            'username' => 'player',
            'metadata' => ['private' => true],
            'data' => [
                'primary' => ['rank_name' => 'Gold', 'playtime_hours' => 12.5, 'rank_image' => ['url' => 'https://example.com/rank.png']],
                'dynamic' => [
                    ['type' => 1, 'name' => 'same', 'value' => 'Champion'],
                    ['type' => 2, 'name' => 'same', 'value' => 0],
                    ['type' => 3, 'name' => 'portrait', 'value' => ['url' => 'https://example.com/player.png']],
                    ['type' => 99, 'name' => 'future', 'value' => ['new' => true]],
                    ['name' => 'partial', 'value' => null],
                ],
            ],
        ];
        $profile = getMockDiscord()->getFactory()->part(ApplicationIdentityProfile::class, $raw, true);
        $data = $profile->data;
        $this->assertInstanceOf(ProfileData::class, $data);
        $this->assertInstanceOf(PrimaryProfileData::class, $data->primary);
        $this->assertInstanceOf(ProfileMedia::class, $data->primary->rank_image);
        $this->assertSame('Gold', $profile->data['primary']['rank_name']);
        $this->assertSame(12.5, $data->primary->playtime_hours);
        $fields = iterator_to_array($data->dynamic);
        $this->assertCount(5, $fields);
        $this->assertInstanceOf(DynamicStringField::class, $fields[0]);
        $this->assertInstanceOf(DynamicNumberField::class, $fields[1]);
        $this->assertInstanceOf(DynamicMediaField::class, $fields[2]);
        $this->assertSame('https://example.com/player.png', $fields[2]->value->url);
        $this->assertSame(DynamicField::class, $fields[3]::class);
        $this->assertSame(['new' => true], $fields[3]->value);
        $this->assertNull($fields[4]->value);
        $this->assertSame($raw, $profile->getRawAttributes());
        $this->assertSame($raw, json_decode(json_encode($profile), true));
        $this->assertTrue($fields[0]->created);
        $profile->created = false;
        $this->assertFalse($fields[0]->created);
    }

    public function testUnknownNestedFieldsDoNotChangeProfileSerialization(): void
    {
        $raw = ['username' => null, 'metadata' => null, 'data' => [
            'future_section' => ['enabled' => true],
            'primary' => ['rank_name' => 'Gold', 'future_stat' => 7],
        ]];
        $profile = getMockDiscord()->getFactory()->part(ApplicationIdentityProfile::class, $raw);
        $this->assertSame('Gold', $profile->data->primary->rank_name);
        $this->assertSame($raw, $profile->getRawAttributes());
        $this->assertSame($raw, json_decode(json_encode($profile), true));
    }
    public function testAbsentNullAndEmptyStatsRemainDistinct(): void
    {
        foreach ([[], ['data' => null], ['data' => []], ['data' => ['primary' => null, 'dynamic' => null]], ['data' => ['primary' => [], 'dynamic' => []]]] as $raw) {
            $profile = getMockDiscord()->getFactory()->part(ApplicationIdentityProfile::class, $raw);
            $data = $profile->data;
            $this->assertSame($raw, $profile->getRawAttributes());
            if (! isset($raw['data'])) {
                $this->assertNull($data);
                continue;
            }
            $this->assertInstanceOf(ProfileData::class, $data);
            if (isset($raw['data']['primary'])) {
                $this->assertInstanceOf(PrimaryProfileData::class, $data->primary);
                $this->assertNull($data->primary->rank_image);
            }
            if (! isset($raw['data']['primary'])) {
                $this->assertNull($data->primary);
            }
            if (isset($raw['data']['dynamic'])) {
                $this->assertCount(0, $data->dynamic);
            }
            if (! isset($raw['data']['dynamic'])) {
                $this->assertNull($data->dynamic);
            }
        }
        $field = getMockDiscord()->getFactory()->part(DynamicMediaField::class, ['type' => 3, 'name' => 'image', 'value' => null]);
        $this->assertNull($field->value);
        $this->assertNull(getMockDiscord()->getFactory()->part(ProfileMedia::class)->url);
    }
}
