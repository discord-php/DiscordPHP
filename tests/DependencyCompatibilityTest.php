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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

final class DependencyCompatibilityTest extends TestCase
{
    public function testSymfonyPsr16CacheRoundTrip(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter());

        $this->assertSame('missing', $cache->get('key', 'missing'));
        $this->assertTrue($cache->set('key', ['value' => false]));
        $this->assertSame(['value' => false], $cache->get('key'));
        $this->assertTrue($cache->delete('key'));
        $this->assertSame('missing', $cache->get('key', 'missing'));
    }
}
