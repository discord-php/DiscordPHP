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

use Discord\Parts\Channel\Message\Reaction;
use Discord\Parts\Guild\Emoji;

/**
 * A reaction built from its identifier: the emoji is an Emoji Part, and a custom one keeps its guild.
 */
final class ReactionTest extends DiscordTestCase
{
    public function testACustomEmojiReactionKeepsItsGuild(): void
    {
        // `fill()` walks `$fillable` in order, and `id` comes before `guild_id`: the Emoji is
        // built while the guild is still unknown, and must pick it up afterwards.
        $reaction = getMockDiscord()->getFactory()->part(Reaction::class, [
            'id' => 'party:123',
            'channel_id' => '7',
            'message_id' => '8',
            'guild_id' => '456',
        ], true);

        $this->assertInstanceOf(Emoji::class, $reaction->emoji);
        $this->assertSame('123', $reaction->emoji->id);
        $this->assertSame('party', $reaction->emoji->name);
        $this->assertSame('456', $reaction->emoji->guild_id);
    }

    public function testAnAnimatedCustomEmojiIsMarkedAnimated(): void
    {
        $reaction = getMockDiscord()->getFactory()->part(Reaction::class, ['id' => 'a:wave:789', 'guild_id' => '456'], true);

        $this->assertSame('789', $reaction->emoji->id);
        $this->assertSame('wave', $reaction->emoji->name);
        $this->assertTrue($reaction->emoji->animated);
    }

    public function testAUnicodeEmojiReactionHasANameAndNoId(): void
    {
        $reaction = getMockDiscord()->getFactory()->part(Reaction::class, ['id' => '👍', 'channel_id' => '7', 'message_id' => '8'], true);

        $this->assertInstanceOf(Emoji::class, $reaction->emoji);
        $this->assertNull($reaction->emoji->id);
        $this->assertSame('👍', $reaction->emoji->name);
    }
}
