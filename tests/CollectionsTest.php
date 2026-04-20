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

final class ClassOne
{
    public $id;
}

final class ClassTwo
{
    public $id;
}

it('creates a collection from an array', function () {
    $array = ['one', 'two', 'three'];
    $collection = Collection::from($array);

    expect($collection->jsonSerialize())->toEqual($array);
});

it('pushes values onto the collection', function () {
    $collection = new Collection([], null);

    $collection->push('test', 'one');
    $collection->push('two');

    expect($collection->jsonSerialize())->toEqual(['test', 'one', 'two']);
});

it('rejects values of a different type', function () {
    $collection = Collection::for(ClassOne::class);

    $obj1 = new ClassOne();
    $obj1->id = 1;

    $obj2 = new ClassOne();
    $obj2->id = 2;

    $wrongClassObject = new ClassTwo();
    $wrongClassObject->id = 3;

    $collection->push($obj1, $obj2, $wrongClassObject);

    expect($collection->jsonSerialize())->toEqual([1 => $obj1, 2 => $obj2]);
});

it('retrieves values by attribute', function () {
    $collection = new Collection([
        ['id' => 12, 'test' => 'something'],
        ['id' => 13, 'test' => 'something else'],
        ['id' => 14, 'test' => 'something even more different'],
    ], 'id');

    expect($collection->get('id', 13))->toEqual(['id' => 13, 'test' => 'something else']);
    expect($collection->get('test', 'something'))->toEqual(['id' => 12, 'test' => 'something']);
});

it('pulls a value by key and removes it', function () {
    $array = [1, 2, 3, 4, 5];
    $collection = new Collection($array, null);

    expect($collection->pull(2))->toBe(3);

    unset($array[2]);
    expect($collection->jsonSerialize())->toEqual($array);
});

it('pull returns default value when key not found', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->pull(10, 'default'))->toBe('default');
});

it('fills the collection from an array', function () {
    $collection = new Collection([], null);
    $collection->fill([1, 2, 3, 4, 5]);

    expect($collection->jsonSerialize())->toEqual([1, 2, 3, 4, 5]);
});

it('counts elements correctly', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->count())->toBe(5);
});

it('returns the first element', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->first())->toBe(1);
});

it('returns the last element', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->last())->toBe(5);
});

it('checks isset correctly', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->isset(0))->toBeTrue();
    expect($collection->isset(5))->toBeFalse();
});

it('checks has for multiple keys', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->has(1, 2, 3))->toBeTrue();
    expect($collection->has(0))->toBeTrue();
    expect($collection->has(5, 6, 7))->toBeFalse();
    expect($collection->has(0, 5))->toBeFalse();
});

it('filters elements by predicate', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);
    $filtered = $collection->filter(fn (int $n) => $n > 2);

    expect($filtered->jsonSerialize())->toEqual([3, 4, 5]);
});

it('finds the first matching element', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->find(fn (int $n) => $n === 2))->toBe(2);
});

it('find returns null when no element matches', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);

    expect($collection->find(fn () => false))->toBeNull();
});

it('clears all elements', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);
    $collection->clear();

    expect($collection->jsonSerialize())->toEqual([]);
});

it('maps elements with a callback', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);
    $mapped = $collection->map(fn (int $n) => $n * 2);

    expect($mapped->jsonSerialize())->toEqual([2, 4, 6, 8, 10]);
});

it('merges another collection appending elements', function () {
    $collection = new Collection([1, 2, 3, 4, 5], null);
    $collection->merge(new Collection([6, 7, 8], null));

    expect($collection->jsonSerialize())->toEqual(range(1, 8));
});

it('merge overwrites duplicate keys', function () {
    $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
    $collection->merge(new Collection(['first' => 3, 'second' => 4, 'fourth' => 5], null));

    expect($collection->jsonSerialize())->toEqual([
        'first' => 3,
        'second' => 4,
        'third' => 3,
        'fourth' => 5,
    ]);
});

it('offsetGet returns the value at key', function () {
    $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);

    expect($collection->offsetGet('second'))->toBe(2);
});

it('offsetSet updates the value at key', function () {
    $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
    $collection->offsetSet('second', 4);

    expect($collection->jsonSerialize())->toEqual(['first' => 1, 'second' => 4, 'third' => 3]);
});

it('offsetUnset removes the key', function () {
    $collection = new Collection(['first' => 1, 'second' => 2, 'third' => 3], null);
    $collection->offsetUnset('second');

    expect($collection->jsonSerialize())->toEqual(['first' => 1, 'third' => 3]);
});

it('is iterable via foreach', function () {
    $collection = new Collection(range(1, 10), null);

    $collected = [];
    foreach ($collection as $item) {
        $collected[] = $item;
    }

    expect($collected)->toEqual(range(1, 10));
});

