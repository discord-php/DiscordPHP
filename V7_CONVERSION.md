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

## Slash Commands

If you previously linked [DiscordPHP-Slash](https://github.com/discord-php/DiscordPHP-Slash), you can remove the package and change your code:

### Register Client

| |DiscordPHP-Slash|DiscordPHP|
|-----|-----|-----|
|Register Client|`$client = new RegisterClient('your-bot-token-here');`|*Removed*, the `$discord` can deal with commands from REST API, requires `application.commands` scope|
|Get list of all Global Commands|`$commands = $client->getCommands();`|`$discord->application->commands->freshen()->done(function ($commands) { /* ... */ });`|
|Get list of all Guild Commands|`$guildCommands = $client->getCommands('guild_id_here');`|`$discord->guilds['guild_id_here']->commands->freshen()->done(function ($commands) { /* ... */ });`|
|Get a specific Global Command|`$command = $client->getCommand('command_id');`|`$discord->application->commands->fetch('command_id')->done(function ($command) { /* ... */ });`|
|Get a specific Guild Commands|`$command = $client->getCommand('command_id', 'guild_id');`|`$discord->guilds['guild_id']->commands->fetch('command_id')->done(function ($command) { /* ... */ });`|
|Create a Global Command|`$command = $client->createGlobalCommand('command_name', 'command_description', [ /* optional array of options */ ]);`|`$command = new Command($discord, ['name' => 'command_name', 'description' => 'command_description', /* optional array of options */]);`<br/>`$discord->application->commands->save($command)`|
|Create a Guild Command|`$command = $client->createGuildSpecificCommand('guild_id', 'command_name', 'command_description', [     /* optional array of options */ ]);`|`$command = new Command($discord, ['name' => 'command_name', 'description' => 'command_description', /* optional array of options */]);`<br/>`$discord->guilds['guild_id']->commands->save($command)`|
|Updating a Global command|`$command->name = 'newcommandname';`<br/>`$client->updateCommand($command);`|`$command->name = 'newcommandname';`<br/>`$discord->application->commands->save($command);`|
|Updating a Guild command|`$command->name = 'newcommandname';`<br/>`$client->updateCommand($command);`|`$command->name = 'newcommandname';`<br/>`$discord->guilds['guild_id']->commands->save($command);`|
|Deleting a Global command|`$client->deleteCommand($command);`|`$discord->application->commands->delete($command);`|
|Deleting a Guild command|`$client->deleteCommand($command);`|`$discord->guilds['guild_id']->commands->delete($command);`|

### Slash Client

| |DiscordPHP-Slash|DiscordPHP|
|-----|-----|-----|
|Client|`$client = new Client([ /* options */ ]);`|*Removed*, all options are present when constructing `$discord`|
|Link|`$client->linkDiscord($discord, false);`|*Removed*, this is already the `$discord`|
|Register a Command|`$client->registerCommand('hello', function (Interaction $interaction, Choices $choices) {`|`$discord->listenCommand('hello', function (Interaction $interaction) {`<br/>Choices are inside `$interaction->data->options`|
|Acknowledge|`$interaction->acknowledge();`|*Same as below*|
|Acknowledge with source|`$interaction->acknowledge(true);`|`$interaction->acknowledgeWithResponse();`|
|Reply|`$interaction->reply('Hello world!');`|*Same as below*|
|Reply with source|`$interaction->replyWithSource('Hello world!');`|`$interaction->respondWithMessage(MessageBuilder::new()->setContent('Hello world!'));`|
|Update initial response|`$interaction->updateInitialResponse('text');`|`$interaction->updateOriginalResponse(MessageBuilder::new()->setContent('text'));`|
|Delete initial response|`$interaction->deleteInitialResponse();`|`$interaction->deleteOriginalResponse();`|
|Send a follow up message|`$interaction->sendFollowUpMessage('text');`|`$interaction->sendFollowUpMessage(MessageBuilder::new()->setContent('text'));`|
|Update follow up message|`$interaction->updateFollowUpMessage('message_id', 'text');`|`$interaction->updateFollowUpMessage('message_id', MessageBuilder::new()->setContent('text'));`|
|Delete follow up message|`$interaction->deleteFollowUpMessage('message_id');`|`$interaction->deleteFollowUpMessage('message_id');`|
|ApplicationCommandOptionType|`ApplicationCommandOptionType::x`<br/>`ApplicationCommandOptionType::SUB_COMMAND`|`Option::x`<br/>`Option::SUB_COMMAND`|
