<?php declare(strict_types=1);

use Discord\Discord;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Author;
use Discord\Parts\Embed\Embed;
use Discord\Parts\Embed\Footer;
use PHPUnit\Framework\TestCase;

use function Discord\getColor;

final class EmbedMessageText extends TestCase
{
    /**
     * @depends DiscordTest::testCanGetChannel
     */
    public function testCanSendEmbed(Channel $channel)
    {
        return wait(function (Discord $discord, $resolve) use ($channel) {
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

            $channel->sendEmbed($embed)->done(function (Message $message) use ($resolve) {
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
                $this->assertNotFalse(isset($embed->fields['Field 1']));
                $this->assertNotFalse(isset($embed->fields['Field 2']));

                $this->assertNotEquals(
                    (string) $embed->fields['Field 1'],
                    (string) $embed->fields['Field 2']
                );

                $resolve();
            });
        }, 10);
    }
}
