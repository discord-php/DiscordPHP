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

use Discord\Helpers\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionsTest extends TestCase
{
    public function testFrom()
    {
        $array = ['one', 'two', 'three'];
        $collection = Collection::from($array);

        $this->assertEquals($array, $collection->toArray());
    }

    public function testPush()
    {
        $collection = new Collection([], null);

        $collection->push('test', 'one');
        $collection->push('two');

        $this->assertEquals(
            ['test', 'one', 'two'],
            $collection->toArray(),
        );
    }

    public function testDontAllowValuesOfDifferentType()
    {
        $collection = Collection::for(ClassOne::class);

        $obj1 = new ClassOne();
        $obj1->id = 1;

        $obj2 = new ClassOne();
        $obj2->id = 2;

        $wrongClassObject = new ClassTwo();
        $wrongClassObject->id = 3;

        $array = [$obj1, $obj2, $wrongClassObject];

        $collection->push(...$array);

        $this->assertEquals([
            1 => $obj1,
            2 => $obj2,
        ], $collection->toArray());
    }

    public function testGet()
    {
        $collection = new Collection([
            [
                'id' => 12,
                'test' => 'something',
            ],
            [
                'id' => 13,
                'test' => 'something else',
            ],
            [
                'id' => 14,
                'test' => 'something even more different',
            ],
        ], 'id');

        $this->assertEquals(
            [
                'id' => 13,
                'test' => 'something else',
            ],
            $collection->get('id', 13)
        );

        $this->assertEquals(
            [
                'id' => 12,
                'test' => 'something',
            ],
            $collection->get('test', 'something')
        );
    }

    public function testPull()
    {
        $array = [1, 2, 3, 4, 5];
        $collection = new Collection($array, null);

        $this->assertEquals(
            3,
            $collection->pull(2)
        );

        unset($array[2]);

        $this->assertEquals(
            $array,
            $collection->toArray()
        );
    }

    public function testPullReturnsDefaultIfKeyNotFound()
    {
        $array = [1, 2, 3, 4, 5];
        $collection = new Collection($array, null);

        $this->assertEquals('default', $collection->pull(10, 'default'));
    }

    public function testFill()
    {
        $collection = new Collection([], null);
        $collection->fill([1, 2, 3, 4, 5]);

        $this->assertEquals([1, 2, 3, 4, 5], $collection->toArray());
    }

    public function testCount()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(5, $collection->count());
    }

    public function testFirst()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(1, $collection->first());
    }

    public function testLast()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(5, $collection->last());
    }

    public function testIsset()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(true, $collection->isset(0));
        $this->assertEquals(false, $collection->isset(5));
    }

    public function testHas()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(true, $collection->has(1, 2, 3));
        $this->assertEquals(true, $collection->has(0));
        $this->assertEquals(false, $collection->has(5, 6, 7));
        $this->assertEquals(false, $collection->has(0, 5));
    }

    public function testFilter()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $filteredCollection = $collection->filter(function (int $number) {
            return $number > 2;
        });

        $this->assertEquals([3, 4, 5], $filteredCollection->toArray());
    }

    public function testFind()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(2, $collection->find(function (int $number) {
            return $number === 2;
        }));
    }

    public function testFindReturnsNullWhenNoResultsFound()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(null, $collection->find(function (int $number) {
            return false;
        }));
    }

    public function testClear()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $collection->clear();

        $this->assertEquals([], $collection->toArray());
    }

    public function testMap()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $mappedArray = $collection->map(function (int $number) {
            return $number * 2;
        });

        $this->assertEquals([
            2, 4, 6, 8, 10,
        ], $mappedArray->toArray());
    }

    public function testMerge()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $collection2 = new Collection([6, 7, 8], null);

        $collection->merge($collection2);

        $this->assertEquals(
            range(1, 8),
            $collection->toArray()
        );
    }

    public function testMergeKeysAreOverwritten()
    {
        $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
        $collection2 = new Collection(['first' => 3, 'second' => 4, 'fourth' => 5], null);

        $collection->merge($collection2);

        $this->assertEquals(
            [
                'first' => 3,
                'second' => 4,
                'third' => 3,
                'fourth' => 5,
            ],
            $collection->toArray()
        );
    }

    public function testOffsetGet()
    {
        $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);

        $this->assertEquals(2, $collection->offsetGet('second'));
    }

    public function testOffsetSet()
    {
        $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
        $collection->offsetSet('second', 4);

        $this->assertEquals(['first' => 1, 'second' => 4, 'third' => 3], $collection->toArray());
    }

    public function testOffsetUnset()
    {
        $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
        $collection->offsetUnset('second');

        $this->assertEquals(['first' => 1, 'third' => 3], $collection->toArray());
    }

    public function testIsIterable()
    {
        $collection = new Collection(range(1, 10), null);

        $collected = [];

        foreach ($collection as $item) {
            $collected[] = $item;
        }

        $this->assertEquals(range(1, 10), $collected);
    }
}

final class ClassOne
{
    public $id;
}

final class ClassTwo
{
    public $id;
}
