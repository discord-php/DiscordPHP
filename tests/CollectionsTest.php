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

use Discord\Helpers\Collection;

final class CollectionsTest extends DiscordTestCase
{
    public function testFrom()
    {
        $array = ['one', 'two', 'three'];
        $collection = Collection::from($array);

        $this->assertEquals($array, $collection->jsonSerialize());
    }

    public function testPush()
    {
        $collection = new Collection([], null);

        $collection->push('test', 'one');
        $collection->push('two');

        $this->assertEquals(
            ['test', 'one', 'two'],
            $collection->jsonSerialize(),
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
        ], $collection->jsonSerialize());
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
            $collection->jsonSerialize()
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

        $this->assertEquals([1, 2, 3, 4, 5], $collection->jsonSerialize());
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

        $this->assertTrue($collection->isset(0));
        $this->assertFalse($collection->isset(5));
    }

    public function testHas()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertTrue($collection->has(1, 2, 3));
        $this->assertTrue($collection->has(0));
        $this->assertFalse($collection->has(5, 6, 7));
        $this->assertFalse($collection->has(0, 5));
    }

    public function testFilter()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $filteredCollection = $collection->filter(fn (int $number) => $number > 2);

        $this->assertEquals([3, 4, 5], $filteredCollection->jsonSerialize());
    }

    public function testFind()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(2, $collection->find(fn (int $number) => $number === 2));
    }

    public function testFindReturnsNullWhenNoResultsFound()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertEquals(null, $collection->find(fn (int $number) => false));
    }

    public function testClear()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $collection->clear();

        $this->assertEquals([], $collection->jsonSerialize());
    }

    public function testMap()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $mappedArray = $collection->map(fn (int $number) => $number * 2);

        $this->assertEquals([
            2, 4, 6, 8, 10,
        ], $mappedArray->jsonSerialize());
    }

    public function testMerge()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);
        $collection2 = new Collection([6, 7, 8], null);

        $collection->merge($collection2);

        $this->assertEquals(
            range(1, 8),
            $collection->jsonSerialize()
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
            $collection->jsonSerialize()
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

        $this->assertEquals(['first' => 1, 'second' => 4, 'third' => 3], $collection->jsonSerialize());
    }

    public function testOffsetUnset()
    {
        $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
        $collection->offsetUnset('second');

        $this->assertEquals(['first' => 1, 'third' => 3], $collection->jsonSerialize());
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

    public function testForBuildsAnEmptyClassRestrictedCollection()
    {
        $collection = Collection::for(ClassOne::class, 'id');

        $this->assertSame([], $collection->toArray());
        $this->assertSame(0, $collection->count());
    }

    public function testPushItemKeysByDiscriminatorAndAppendsWhenNull()
    {
        $collection = new Collection([], 'id');

        $a = ['id' => 5, 'name' => 'a'];
        $b = ['id' => 9, 'name' => 'b'];
        $c = ['name' => 'no-id'];

        $collection->pushItem($a)->pushItem($b)->pushItem($c);

        $this->assertSame($a, $collection->get('id', 5));
        $this->assertSame($b, $collection->get('id', 9));
        // An item with no discriminator is appended rather than keyed.
        $this->assertSame($c, $collection->last());
        $this->assertSame(3, $collection->count());
    }

    public function testGetMatchesObjectProperties()
    {
        $one = new ClassOne();
        $one->id = 'x';
        $two = new ClassOne();
        $two->id = 'y';

        $collection = new Collection([$one, $two], 'id', ClassOne::class);

        $this->assertSame($two, $collection->get('id', 'y'));
        $this->assertNull($collection->get('id', 'missing'));
    }

    public function testSet()
    {
        $collection = new Collection([], null);

        $collection->set('key', 'value');

        $this->assertSame('value', $collection->offsetGet('key'));
    }

    public function testShift()
    {
        $collection = new Collection([1, 2, 3], null);

        // shift() returns the removed [key => value] pair.
        $this->assertSame([0 => 1], $collection->shift());
        $this->assertSame([2, 3], $collection->values());
        $this->assertSame(2, $collection->count());
    }

    public function testShiftReturnsNullWhenEmpty()
    {
        $this->assertNull((new Collection([], null))->shift());
    }

    public function testSearch()
    {
        $collection = new Collection(['a', 'b', 'c'], null);

        $this->assertSame(1, $collection->search('b'));
        $this->assertFalse($collection->search('z'));
        $this->assertFalse($collection->search('1', true));
        $this->assertSame(0, $collection->search('a', true));
    }

    public function testFindKey()
    {
        $collection = new Collection([10, 20, 30], null);

        $this->assertSame(1, $collection->find_key(fn ($n) => $n === 20));
        $this->assertNull($collection->find_key(fn ($n) => $n === 999));
    }

    public function testAnyAndAll()
    {
        $collection = new Collection([2, 4, 6], null);

        $this->assertTrue($collection->any(fn ($n) => $n === 4));
        $this->assertFalse($collection->any(fn ($n) => $n === 5));
        $this->assertTrue($collection->all(fn ($n) => $n % 2 === 0));
        $this->assertFalse($collection->all(fn ($n) => $n > 2));
    }

    public function testSplice()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        // splice() mutates in place and returns the collection.
        $returned = $collection->splice(1, 2, ['a', 'b', 'c']);

        $this->assertSame($collection, $returned);
        $this->assertSame([1, 'a', 'b', 'c', 4, 5], $collection->values());
    }

    public function testSlice()
    {
        $collection = new Collection([1, 2, 3, 4, 5], null);

        $this->assertSame([2, 3, 4], $collection->slice(1, 3)->values());
        $this->assertSame([4, 5], $collection->slice(-2)->values());
        // The original collection is untouched.
        $this->assertSame([1, 2, 3, 4, 5], $collection->values());
    }

    public function testSortWithCallback()
    {
        $collection = new Collection([3, 1, 2], null);

        // sort() returns a new, sorted collection.
        $sorted = $collection->sort(fn ($a, $b) => $a <=> $b);

        $this->assertSame([1, 2, 3], $sorted->values());
    }

    public function testSortWithFlag()
    {
        $collection = new Collection(['banana', 'apple', 'cherry'], null);

        $sorted = $collection->sort(SORT_STRING);

        $this->assertSame(['apple', 'banana', 'cherry'], $sorted->values());
    }

    public function testDiff()
    {
        $a = new Collection([1, 2, 3, 4], null);
        $b = new Collection([2, 4], null);

        $this->assertSame([1, 3], array_values($a->diff($b)->toArray()));
    }

    public function testIntersect()
    {
        $a = new Collection([1, 2, 3, 4], null);
        $b = new Collection([2, 4, 5], null);

        $this->assertSame([2, 4], array_values($a->intersect($b)->toArray()));
    }

    public function testWalk()
    {
        $collection = new Collection([1, 2, 3], null);
        $seen = [];

        $collection->walk(function ($value, $key) use (&$seen) {
            $seen[$key] = $value;
        });

        $this->assertSame([1, 2, 3], array_values($seen));
    }

    public function testReduce()
    {
        $collection = new Collection([1, 2, 3, 4], null);

        $this->assertSame(10, $collection->reduce(fn ($carry, $item) => $carry + $item, 0));
    }

    public function testUnique()
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3], null);

        $this->assertSame([1, 2, 3], array_values($collection->unique()->toArray()));
    }

    public function testKeysAndValues()
    {
        $collection = new Collection(['a' => 1, 'b' => 2], null);

        $this->assertSame(['a', 'b'], $collection->keys());
        $this->assertSame([1, 2], $collection->values());
    }

    public function testSerializeRoundTrip()
    {
        $collection = new Collection([1, 2, 3], null);

        $restored = new Collection([], null);
        $restored->unserialize($collection->serialize());

        $this->assertSame([1, 2, 3], $restored->values());
    }

    public function testNativeSerializeRoundTrip()
    {
        $collection = new Collection(['x' => 1, 'y' => 2], null);

        /** @var Collection $restored */
        $restored = unserialize(serialize($collection));

        $this->assertSame(['x' => 1, 'y' => 2], $restored->toArray());
    }

    public function testDebugInfoReturnsItems()
    {
        $collection = new Collection([1, 2, 3], null);

        $this->assertSame([1, 2, 3], $collection->__debugInfo());
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
