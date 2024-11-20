<?php

declare(strict_types=1);

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Footer;

use function Discord\getColor;

final class EmbedMessageTest extends DiscordTestCase
{
    /**
     * @covers \Discord\Parts\Channel\Channel::sendEmbed
     */
    public function testCanSendEmbed()
    {
        return wait(function (Discord $discord, $resolve) {
            $embed = new Embed($discord);
            $embed->setTitle('Testing Embed')
                ->setType(Embed::TYPE_RICH)
                ->setAuthor('DiscordPHP Bot')
                ->setDescription('Embed Description')
                ->setColor(getColor('lightblue'))
                ->addField([
                    'name' => 'Field 1',
                    'value' => 'Value 1',
                    'inline' => true,
                ])
                ->addField([
                    'name' => 'Field 2',
                    'value' => 'Value 2',
                    'inline' => false,
                ])
                ->setFooter('Footer Value');

            $this->channel()->sendEmbed($embed)
                ->then(function (Message $message) use ($resolve) {
                    $this->assertEquals(1, $message->embeds->count());

                    /** @var Embed */
                    $embed = $message->embeds->first();
                    $this->assertEquals('Testing Embed', $embed->title);
                    $this->assertEquals(Embed::TYPE_RICH, $embed->type);
                    $this->assertEquals('Embed Description', $embed->description);
                    $this->assertEquals(getColor('lightblue'), $embed->color);

                    $this->assertInstanceOf(Author::class, $embed->author);
                    $this->assertEquals('DiscordPHP Bot', $embed->author->name);

                    $this->assertInstanceOf(Footer::class, $embed->footer);
                    $this->assertEquals('Footer Value', $embed->footer->text);

                    $this->assertEquals(2, $embed->fields->count());
                    $this->assertNotNull($embed->fields->get('name', 'Field 1'));
                    $this->assertNotNull($embed->fields->get('name', 'Field 2'));

                    $this->assertNotEquals(
                        (string) $embed->fields->get('name', 'Field 1'),
                        (string) $embed->fields->get('name', 'Field 2')
                    );
                })
                ->done($resolve, $resolve);
        }, 10);
    }
}
