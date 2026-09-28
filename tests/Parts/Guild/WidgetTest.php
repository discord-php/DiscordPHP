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
use Discord\Parts\Guild\Widget;
use React\Http\Message\Response;

final class WidgetTest extends DiscordTestCase
{
    public function testTheImageIsDownloadedAsPng()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock, $driver] = $this->clientWith(fn () => new Response(200, ['Content-Type' => 'image/png'], "\x89PNG\r\n\x1a\nimage"));
            $widget = $mock->getFactory()->part(Widget::class, ['id' => '10'], true);

            $widget->getImage(Widget::STYLE_BANNER2)
                ->then(function ($png) use ($driver) {
                    $this->assertSame("\x89PNG\r\n\x1a\nimage", $png);
                    $this->assertSame('GET', $driver->requests[0]['method']);
                    $this->assertStringEndsWith('/guilds/10/widget.png?style=banner2', $driver->requests[0]['url']);
                    $this->assertArrayNotHasKey('Authorization', $driver->requests[0]['headers'], 'the image needs no authentication');
                })
                ->then($resolve, $resolve);
        });
    }

    public function testAnUnknownStyleIsLeftOut()
    {
        $widget = getMockDiscord()->getFactory()->part(Widget::class, ['id' => '10'], true);

        $this->assertStringEndsWith('/guilds/10/widget.png', $widget->getImageAttribute('sparkly'));
        $this->assertStringEndsWith('/guilds/10/widget.png?style=shield', $widget->image);
    }

    public function testAFailedDownloadRejects()
    {
        return wait(function (Discord $discord, $resolve) {
            [$mock] = $this->clientWith(fn () => new Response(404, ['Content-Type' => 'application/json'], '{"message": "Unknown Guild", "code": 10004}'));
            $widget = $mock->getFactory()->part(Widget::class, ['id' => '10'], true);

            $widget->getImage()
                ->then(
                    fn () => $this->fail('A 404 is not an image.'),
                    function (\Throwable $e) {
                        $this->assertInstanceOf(\RuntimeException::class, $e);
                        $this->assertSame(404, $e->getCode());
                    },
                )
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
