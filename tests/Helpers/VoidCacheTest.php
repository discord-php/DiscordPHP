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

use Discord\Helpers\VoidCache;
use PHPUnit\Framework\TestCase;

final class VoidCacheTest extends TestCase
{
    private VoidCache $cache;

    protected function setUp(): void
    {
        $this->cache = new VoidCache();
    }

    public function testGetReturnsNullByDefault(): void
    {
        $this->assertNull($this->cache->get('key'));
    }

    public function testGetReturnsProvidedDefault(): void
    {
        $this->assertSame('default', $this->cache->get('key', 'default'));
        $this->assertSame(42, $this->cache->get('key', 42));
    }

    public function testSetReturnsTrue(): void
    {
        $this->assertTrue($this->cache->set('key', 'value'));
    }

    public function testDeleteReturnsTrue(): void
    {
        $this->assertTrue($this->cache->delete('key'));
    }

    public function testClearReturnsTrue(): void
    {
        $this->assertTrue($this->cache->clear());
    }

    public function testHasReturnsFalse(): void
    {
        $this->assertFalse($this->cache->has('key'));
    }

    public function testGetMultipleReturnsDefaultForAllKeys(): void
    {
        $keys = ['a', 'b', 'c'];
        $result = $this->cache->getMultiple($keys, 'default');

        $this->assertSame([
            'a' => 'default',
            'b' => 'default',
            'c' => 'default',
        ], $result);
    }

    public function testGetMultipleReturnsNullDefaultWhenNotSpecified(): void
    {
        $result = $this->cache->getMultiple(['x', 'y']);

        $this->assertSame(['x' => null, 'y' => null], $result);
    }

    public function testSetMultipleReturnsTrue(): void
    {
        $this->assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2]));
    }

    public function testDeleteMultipleReturnsTrue(): void
    {
        $this->assertTrue($this->cache->deleteMultiple(['a', 'b']));
    }

    public function testSetDoesNotPersistValue(): void
    {
        $this->cache->set('key', 'value');

        $this->assertNull($this->cache->get('key'));
        $this->assertFalse($this->cache->has('key'));
    }
}
