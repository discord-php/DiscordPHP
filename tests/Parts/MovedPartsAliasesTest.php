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

use Discord\Parts\Channel\Message\Attachment;
use Discord\Parts\Channel\Message\Embed\Embed;
use Discord\Parts\Channel\Message\Embed\Field;
use Discord\Parts\Channel\Message\Message;
use Discord\Parts\Channel\Message\Reaction;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Parts that moved to follow the API routes (#943) keep working under their old names: in type hints,
 * instanceof, Factory::part(), subclasses, and data cached (serialised) before the move.
 */
final class MovedPartsAliasesTest extends DiscordTestCase
{
    public static function moves(): array
    {
        return [
            'Attachment' => ['Discord\Parts\Channel\Attachment', Attachment::class],
            'Message' => ['Discord\Parts\Channel\Message', Message::class],
            'Reaction' => ['Discord\Parts\Channel\Reaction', Reaction::class],
            'ReactionCountDetails' => ['Discord\Parts\Channel\ReactionCountDetails', 'Discord\Parts\Channel\Message\ReactionCountDetails'],
            'Embed' => ['Discord\Parts\Embed\Embed', Embed::class],
            'Field' => ['Discord\Parts\Embed\Field', Field::class],
            'Author' => ['Discord\Parts\Embed\Author', 'Discord\Parts\Channel\Message\Embed\Author'],
            'Footer' => ['Discord\Parts\Embed\Footer', 'Discord\Parts\Channel\Message\Embed\Footer'],
            'Image' => ['Discord\Parts\Embed\Image', 'Discord\Parts\Channel\Message\Embed\Image'],
            'Provider' => ['Discord\Parts\Embed\Provider', 'Discord\Parts\Channel\Message\Embed\Provider'],
            'Thumbnail' => ['Discord\Parts\Embed\Thumbnail', 'Discord\Parts\Channel\Message\Embed\Thumbnail'],
            'Video' => ['Discord\Parts\Embed\Video', 'Discord\Parts\Channel\Message\Embed\Video'],
        ];
    }

    #[DataProvider('moves')]
    public function testTheOldNameIsTheSameClass(string $old, string $new)
    {
        // Registered when Composer loads, so nothing has to mention the old name first.
        $this->assertTrue(class_exists($old, false), "$old is registered without autoloading");
        $this->assertSame($new, (new ReflectionClass($old))->getName());
    }

    #[DataProvider('moves')]
    public function testAPartMadeUnderEitherNameIsBoth(string $old, string $new)
    {
        $factory = getMockDiscord()->getFactory();

        foreach ([$factory->part($old), $factory->part($new)] as $part) {
            $this->assertInstanceOf($old, $part);
            $this->assertInstanceOf($new, $part);
        }
    }

    public function testOldTypeHintsAcceptTheMovedParts()
    {
        $takesOld = static fn (\Discord\Parts\Channel\Message $message, \Discord\Parts\Embed\Embed $embed): string => $message::class.' '.$embed::class;

        $factory = getMockDiscord()->getFactory();

        $this->assertSame(Message::class.' '.Embed::class, $takesOld($factory->part(Message::class), $factory->part(Embed::class)));
    }

    public function testAClassExtendingAnOldNameStillWorks()
    {
        $subclass = new class(getMockDiscord(), ['title' => 'Hi']) extends \Discord\Parts\Embed\Embed {
        };

        $this->assertInstanceOf(Embed::class, $subclass);
        $this->assertSame('Hi', $subclass->title);
    }

    public function testDataSerialisedUnderAnOldNameStillUnserialises()
    {
        // What a persistent cache holds from before the move: the old class name in the payload.
        $field = getMockDiscord()->getFactory()->part(Field::class, ['name' => 'a', 'value' => 'b']);
        $serialised = str_replace(
            's:'.strlen(Field::class).':"'.Field::class.'"',
            's:'.strlen('Discord\Parts\Embed\Field').':"Discord\Parts\Embed\Field"',
            str_replace('O:'.strlen(Field::class).':"'.Field::class.'"', 'O:'.strlen('Discord\Parts\Embed\Field').':"Discord\Parts\Embed\Field"', serialize($field)),
        );
        $this->assertStringContainsString('"Discord\Parts\Embed\Field"', $serialised);

        $restored = unserialize($serialised);

        $this->assertInstanceOf(Field::class, $restored);
    }
}
