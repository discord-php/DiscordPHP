# Version 7.x to 10.x Conversion Guide

We skipped v8 and v9 to match major changes with the Discord API version 10 as well the PHP version 8

## PHP 8

Support to PHP versions prior to 8.0 has been dropped, v7.x is the latest to support PHP version 7.4

## Depdencies

### Added

- `react/event-loop` The requirement is moved from `discord-php/http` as the HTTP lib may be event loopless in future
- `react/promise` The requirement is moved from `discord-php/http` as the HTTP lib can use promise driver other than ReactPHP's
- `react/async` To support `coroutine()` (For PHP 8.0) and `await()` (For PHP 8.1+)
- `react/cache` for custom cache implementation based on ReactPHP
- `psr/simple-cache` for custom cache implementation based on PSR
- `ext-gmp` is now required to be used in the x86 (32 bits) PHP

### Upgraded

- `react/datagram` 1.5.x to 1.8.x+
- `discord-php/http` 9.0.12+ to 10.1.7+

### Removed

- `react/partial` as we no longer use partial bindings
- `mollie/polyfill-libsodium` as the sodium extension is bundled with PHP 7.2+
- `react/http` The requirement is moved to our `discord-php/http` as the HTTP library may have flexible versions
- The DCA library, voice processing is now handled purely with PHP with help of FFmpeg binary, you may safely remove the dca binaries.

# Breaking Changes

## Changed

- Running x86 (32 bits) PHP without GMP extension enabled is now an error.
- Gateway version from 9 to 10
- The `MESSAGE_CONTENT` intent is required to be explicitly set in the `intents` option as it is now excluded from `Intents::getDefaultIntents()` for using v10 Gateway
- `SelectMenu` class is now abstract, please use either `StringSelect` (as previously in v7.x), `UserSelect`, `RoleSelect`, `MentionableSelect`, or `ChannelSelect` for the new more specific select types
- Internal helper class `Bitwise` is renamed to `BigInt`
- Gateway ZLib Decompressor no longer rely on Clue's ReactPHP Zlib library, but the builtin ZLib [InflateContext](https://www.php.net/manual/en/class.inflatecontext.php) introduced in PHP 8
- Runtime exceptions dealing with lengths that was previously `OutOfBoundsException` are now `OutOfRangeException`
- Instances of `Exception` in the gateway dispatch handler will be a rejected promise and logged as error, while instances of `Error` will throw. This is useful to code that uses `coroutine()` to handle exceptions more precisely
- `Activity::TYPE_PLAYING` is renamed to `Activity::TYPE_GAME`
- `Channel::allowVoice()` is renamed to `Channel::isVoiceBased()` to avoid the ambiguity that enables the channel voice when it's actually just a check if the channel has voice ability.
- Default logger Monolog now use line formatter so that errors are more readable

## Deprecated

- The `ready` event is now `init`, this was a confusion to actual `Event::READY` from the gateway
```diff
- $discord->on('ready', function (Discord $discord) {
+ $discord->on('init', function (Discord $discord) {
```
- `Component::TYPE_SELECT_MENU` is now `Component::TYPE_STRING_SELECT`
- `Discord::factory()`, please use `new $class($discord, ...)` instead. However per-part class factory is not deprecated.
```diff
- $discord->factory(Guild:class, ...);
+ new Guild($discord, ...);
```

## Removed

- The `pmChannels` has been removed from the options, it was never been used in v7.x
- `InviteInvalidException` has been removed, it was never been used in v7.x (bots had been to be added manually by server administrator for long time)
- `Http $http` parameter in `Factory` class, it will now use the same Http Client provided in the `$discord` object

# Miscellaneous

In order to prepare support for ReactPHP v3 in future, and if you previously followed our documentation with handling promises `then()` and `done()`, we hope you change your existing code from:
```php
->done(function (Part $part) {
    // your code
});
```

To:
```php
->then(function (Part $part) {
    // your code
})
->done();
```

This is because the `done()` method is removed in ReactPHP v3 and thus changing the `done()` method to be parameterless helps you to delete the code easier in future.

We are also working on a work-around that adds back the `done()` method but deprecates it for our `Promise`s returns, so you can leave the `done()` in the last line without having error. However it is encouraged to do so, as this work-around only applies to this library, and not general `Promise` returned by other library.