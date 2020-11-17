---
title: "Parts"
---

Parts is the term used for the data structures inside Discord.
All parts share a common set of attributes and methods.

Parts have a set list of fillable fields. If you attempt to set a field that is not accessible, it will not warn you.

To create a part object, you can use the `new` syntax or the `factory` method. For example, creating a `Message` part:

```php
$message = new Message($discord);
// or
$message = $discord->factory->create(Message::class);
```
<br>

Part attributes can be accessed similar to an object or like an array:

```php
$message->content = 'hello!';
// or
$message['content'] = 'hello!';

echo $message->content;
// or
echo $message['content'];
```

### Filling a part with data

The `->fill(array $attributes)` function takes an array of attributes to fill the part. If a field is found that is not 'fillable', it is skipped.

```php
$message->fill([
    'content' => 'hello!',
]);
```

### Getting the raw attributes of a part

The `->getRawAttributes()` function returns the array representation of the part.

```php
$attributes = $message->getRawAttributes();
/**
 * [
 *     "id" => "",
 *     "content" => "",
 *     // ...
 * ]
 */
```
