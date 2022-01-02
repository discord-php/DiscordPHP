# Version 6.x to 7.x Conversion Guide

Another breaking change unfourtunately, due to various factors including the addition of thread support as well
as future proofing for message components (buttons).

## Sending messages

Sending messages is now done through the [`MessageBuilder`](src/Discord/Builders/MessageBuilder.php) class.
See the [documentation](http://discord-php.github.io/DiscordPHP/) section on the message builder for usage.

This has been added to support a few features:

- Sending multiple embeds in a message.
- Sending multiple files in a message.
- Sending file attachments alongside embeds (e.g. images).
- Unifying `sendMessage` and `sendFile` functions.
- Adding message components.

The following functions have been changed, alongside their new signature:

- `Channel::sendMessage(MessageBuilder $message)`
- `Member::sendMessage(MessageBuilder $message)`
- `User::sendMessage(MessageBuilder $message)`

```php
// Old
$message->channel->sendMessage('hello, world!');

// New
$message->channel->sendMessage(MessageBuilder::new()
    ->setContent('hello, world!'));
```

The following functions have been added:

- `Message::edit(MessageBuilder $builder)`

The following functions have been deprecated:

- `Channel::editMessage(Message $message, MessageBuilder $builder)` - deprecated in favour of `Message::edit(MessageBuilder $builder)`.
- `Channel::sendFile()` - deprecated in favour of `Channel::sendMessage(MessageBuilder $builder)`.
- `Channel::getMessage(string $id)` - deprecated in favour of `Channel::messages::fetch(string $id)`.

## `Message::channel` now return `Channel|Thread`

With the addition of threads, messages can now be sent in text channels OR threads. These are not the same part.

If you depend on a function or property which is only present on `Channel`, you should check the type of `$message->channel`:

```php
$discord->on('message', function (Message $message) {
    if ($message->channel instanceof Channel) {
        // is channel...
    } else {
        // is thread...
    }
});
```

## Message components

Message components (buttons, select menus) are now availabe! See the [documentation](https://discord-php.github.io/DiscordPHP/) on how to use implement these into your bot.