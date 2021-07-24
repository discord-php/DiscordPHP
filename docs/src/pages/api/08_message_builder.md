---
title: "Message Builder"
---

The `MessageBuilder` class is used to describe the contents of a new (or to be updated) message.

A new message builder can be created with the `new` function:

```php
$builder = MessageBuilder::new();
```

Most builder functions return itself, so you can easily chain function calls together for a clean API,
an example is shown on the right.

```php
$channel->sendMessage(MessageBuilder::new()
    ->setContent('Hello, world!')
    ->addEmbed($embed)
    ->addFile('/path/to/file'));
```

#### Setting content

Sets the text content of the message. Throws an `InvalidArgumentException` if the content is greater than 2000 characters.

```php
$builder->setContent('Hello, world!');
```

#### Setting TTS value

Sets the TTS value of the message.

```php
$builder->setTts(true);
```

#### Adding embeds

You can add up to 10 embeds to a message. The embed functions takes `Embed` objects or associative arrays:

```php
$builder->addEmbed($embed);
```

You can also set the embeds from another array of embeds. Note this will remove the current embeds from the message.

```php
$embeds = [...];
$builder->setEmbeds($embeds);
```

#### Replying to a message

Sets the message as replying to another message. Takes a `Message` object.

```php
$discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($builder) {
    $builder->setReplyTo($message);
});
```

#### Adding files to the message

You can add multiple files to a message. The `addFile` function takes a path to a file, as well as an optional filename.

If the filename parameter is ommited, it will take the filename from the path. Throws an exception if the path
does not exist.

```php
$builder->addFile('/path/to/file', 'file.png');
```

You can also add files to messages with the content as a string:

```php
$builder->addFileFromContent('file.txt', 'contents of my file!');
```

You can also remove all files from a builder:

```php
$builder->clearFiles();
```

There is no limit on the number of files you can upload, but the whole request must be less than 8MB (including headers and JSON payload).

#### Adding message components

Adds a message component to the message. You can only add `ActionRow` and `SelectMenu` objects. To add buttons, wrap the button in an `ActionRow` object. Throws an `InvalidArgumentException` if the given component is not an `ActionRow` or `SelectMenu`, or if you already have 5 components in the message.

```php
$component = SelectMenu::new();
$builder->addComponent($component);
```