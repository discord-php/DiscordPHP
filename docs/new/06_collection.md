---
title: "Collection"
---

Collections are exactly what they sound like - collections of items. In DiscordPHP collections are based around the idea of parts, but they can be used for any type of item.

<div>
Collections implement interfaces allowing them to be accessed like arrays, such as:

```php
// square bracket index access
$collec[123] = 'asdf';
echo $collec[123]; // asdf

// foreach loops
foreach ($collec as $item) {
    // ...
}

// json serialization
json_encode($collec);

// array serialization
$collecArray = (array) $collec;

// string serialization
$jsonCollec = (string) $collec; // same as json_encode($collec)
```
</div>

#### Creating a collection

| name    | type           | description                                                        |
| ------- | -------------- | ------------------------------------------------------------------ |
| items   | array          | Array of items for the collection. Default is empty collection     |
| discrim | string or null | The discriminator used to discriminate between parts. Default 'id' |
| class   | string or null | The type of class contained in the collection. Default null        |

```php
// Creates an empty collection with discriminator of 'id' and no class type.
// Any item can be inserted into this collection.
$collec = new Collection();

// Creates an empty collection with no discriminator and no class type.
// Similar to a laravel collection.
$collec = new Collection([], null, null);
```

#### Getting an item

Gets an item from the collection, with a key and value.

| name  | type | description                         |
| ----- | ---- | ----------------------------------- |
| key   | any  | The key to search with              |
| value | any  | The value that the key should match |

```php
// Collection with 3 items, discriminator is 'id', no class type
$collec = new Collection([
    [
        'id' => 1,
        'text' => 'My ID is 1.'
    ],
    [
        'id' => 2,
        'text' => 'My ID is 2.'
    ],
    [
        'id' => 3,
        'text' => 'My ID is 3.'
    ]
]);

// [
//     'id' => 1,
//     'text' => 'My ID is 1.'
// ]
$item = $collec->get('id', 1);

// [
//     'id' => 1,
//     'text' => 'My ID is 1.'
// ]
$item = $collec->get('text', 'My ID is 1.');
```

#### Adding an item

Adds an item to the collection. Note that if `class` is set in the constructor and the class of the item inserted is not the same, it will not insert.

| name  | type | description        |
| ----- | ---- | ------------------ |
| $item | any  | The item to insert |

```php
// empty, no discrim, no class
$collec = new Collection([], null, null);

$collec->push(1);
$collec->push('asdf');
$collec->push(true);

// ---

class X
{
    public $y;

    public function __construct($y)
    {
        $this->y = $y;
    }
}

// empty, discrim 'y', class X
$collec = new Collection([], 'y', X::class);
$collec->push(new X(123));
$collec->push(123); // won't insert

// new X(123)
$collec->get('y', 123);
```

#### Pulling an item

Removes an item from the collection and returns it.

| name    | type | description                               |
| ------- | ---- | ----------------------------------------- |
| key     | any  | The key to look for                       |
| default | any  | Default if key is not found. Default null |

```php
$collec = new Collection([], null, null);
$collec->push(1);
$collec->push(2);
$collec->push(3);

$collec->pull(1); // returns at 1 index - which is actually 2
$collec->pull(100); // returns null
$collec->pull(100, 123); // returns 123
```

#### Filling the collection

Fills the collection with an array of items.

```php
$collec = new Collection([], null, null);
$collec->fill([
    1, 2, 3, 4,
]);
```

#### Number of items

Returns the number of items in the collection.

```php
$collec = new Collection([
    1, 2, 3
], null, null);

echo $collec->count(); // 3
```

#### Getting the first item

Gets the first item of the collection.

```php
$collec = new Collection([
    1, 2, 3
], null, null);

echo $collec->first(); // 1
```

#### Filtering a collection

Filters the collection with a given callback function. The callback function is called for every item and is called with the item. If the callback returns true, the item is added to the new collection. Returns a new collection.

| name     | type     | description                       |
| -------- | -------- | --------------------------------- |
| callback | callable | The callback called on every item |


```php
$collec = new Collection([
    1, 2, 3, 100, 101, 102
], null, null);

// [ 101, 102 ]
$newCollec = $collec->filter(function ($item) {
    return $item > 100;
});
```

#### Clearing a collection

Clears the collection.

```php
$collec->clear(); // $collec = []
```

#### Mapping a collection

A given callback function is called on each item in the collection, and the result is inserted into a new collection.

| name     | type     | description                       |
| -------- | -------- | --------------------------------- |
| callback | callable | The callback called on every item |

```php
$collec = new Collection([
    1, 2, 3, 100, 101, 102
], null, null);

// [ 100, 200, 300, 10000, 10100, 10200 ]
$newCollec = $collec->map(function ($item) {
    return $item * 100;
});
```

#### Converting to array

Converts a collection to an array.

```php
$arr = $collec->toArray();
```
