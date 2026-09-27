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
use Discord\Parts\Channel\Message\TextInput;
use Discord\Parts\Gateway\Identify;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Part;
use Discord\Repository\Guild\MemberRepository;

/**
 * isset() and empty() on a part's magic properties answer as they would for a declared
 * property: set when reading it gives a value other than null.
 */
final class PartIssetTest extends DiscordTestCase
{
    public function testStoredAttributes()
    {
        return wait(function (Discord $discord, $resolve) {
            $part = $this->part(getMockDiscord(), ['name' => 'Valgorithms', 'count' => 0]);

            $this->assertTrue(isset($part->name));
            $this->assertFalse(empty($part->name));
            $this->assertTrue(isset($part->count), 'zero is set');
            $this->assertTrue(empty($part->count), 'and empty');
            $this->assertFalse(isset($part->nothing), 'a fillable attribute never given');
            $this->assertTrue(empty($part->nothing));
            $this->assertFalse(isset($part->not_fillable));

            $resolve();
        });
    }

    public function testAttributesComputedByAMutatorOrARepository()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $part = $this->part($mock, ['name' => 'val']);

            $this->assertTrue(isset($part->shout));
            $this->assertSame('VAL', $part->shout);
            $this->assertFalse(isset($this->part($mock, [])->shout), 'the mutator returns null');

            $guild = $mock->getFactory()->part(Guild::class, ['id' => '1'], true);
            $this->assertTrue(isset($guild->members));
            $this->assertInstanceOf(MemberRepository::class, $guild->members);

            $resolve();
        });
    }

    public function testCollectionsFindPartsByAnyAttribute()
    {
        return wait(function (Discord $discord, $resolve) {
            $mock = getMockDiscord();
            $components = $mock->getCollectionClass()::for(TextInput::class);
            $components->pushItem($mock->getFactory()->part(TextInput::class, ['type' => 4, 'id' => 2, 'custom_id' => 'instructions', 'value' => 'Shorter'], true));

            $this->assertSame('Shorter', $components->get('custom_id', 'instructions')?->value);
            $this->assertNull($components->get('custom_id', 'missing'));

            $resolve();
        });
    }

    public function testIdentifySendsItsOptionalFields()
    {
        return wait(function (Discord $discord, $resolve) {
            $identify = getMockDiscord()->getFactory()->part(Identify::class, [
                'token' => 'token',
                'properties' => [],
                'intents' => 1,
                'compress' => false,
                'large_threshold' => 250,
                'shard' => [1, 4],
                'presence' => ['status' => 'online'],
            ]);

            $this->assertSame([
                'token' => 'token',
                'properties' => [],
                'intents' => 1,
                'compress' => false,
                'large_threshold' => 250,
                'shard' => [1, 4],
                'presence' => ['status' => 'online'],
            ], $identify->jsonSerialize());

            $resolve();
        });
    }

    private function part(Discord $discord, array $attributes): Part
    {
        return new class($discord, $attributes, true) extends Part {
            protected $fillable = ['name', 'count', 'nothing'];

            protected function getShoutAttribute(): ?string
            {
                return isset($this->name) ? strtoupper($this->name) : null;
            }
        };
    }
}
