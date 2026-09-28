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

use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Field;

/**
 * Discord's embed field limits are enforced when a field is added, rather than by a 400 from Discord.
 *
 * @link https://docs.discord.com/developers/resources/message#embed-object-embed-limits
 */
final class EmbedFieldLimitsTest extends DiscordTestCase
{
    public function testTwentyFiveFieldsFitAndTheTwentySixthDoesNot()
    {
        $embed = $this->embed();
        for ($i = 1; $i <= 25; ++$i) {
            $embed->addFieldValues("Field {$i}", 'value');
        }
        $this->assertCount(25, $embed->fields);

        $this->expectException(\OverflowException::class);
        $embed->addFieldValues('Field 26', 'value');
    }

    public function testANameOf256CharactersFitsAnd257DoesNot()
    {
        $embed = $this->embed()->addFieldValues(str_repeat('n', 256), 'value');
        $this->assertCount(1, $embed->fields);

        $this->expectException(\LengthException::class);
        $embed->addFieldValues(str_repeat('n', 257), 'value');
    }

    public function testAValueOf1024CharactersFitsAnd1025DoesNot()
    {
        $embed = $this->embed()->addFieldValues('name', str_repeat('v', 1024));
        $this->assertCount(1, $embed->fields);

        $this->expectException(\LengthException::class);
        $embed->addFieldValues('name', str_repeat('v', 1025));
    }

    public function testLengthsCountCharactersNotBytes()
    {
        $embed = $this->embed()->addFieldValues(str_repeat('é', 256), str_repeat('😀', 1024));

        $this->assertCount(1, $embed->fields);
    }

    public function testFieldsCountTowardsTheEmbedsTotal()
    {
        // 4096 + 1024 + 880 = 6000 characters: at the limit.
        $embed = $this->embed()->setDescription(str_repeat('d', 4096))->addFieldValues(str_repeat('a', 256), str_repeat('v', 768));
        $embed->addFieldValues(str_repeat('b', 256), str_repeat('v', 624));
        $this->assertCount(2, $embed->fields);

        $this->expectException(\LengthException::class);
        $embed->addFieldValues('n', 'v');
    }

    public function testAFieldPartIsCheckedToo()
    {
        $field = getMockDiscord()->getFactory()->part(Field::class, ['name' => 'name', 'value' => str_repeat('v', 1025)]);

        $this->expectException(\LengthException::class);
        $this->embed()->addField($field);
    }

    public function testAFailedFieldIsNotAdded()
    {
        $embed = $this->embed();

        try {
            $embed->addFieldValues('name', str_repeat('v', 1025));
        } catch (\LengthException) {
        }

        $this->assertCount(0, $embed->fields);
    }

    private function embed(): Embed
    {
        return getMockDiscord()->getFactory()->part(Embed::class);
    }
}
