---
title: "Intro"
---

DiscordPHP is a wrapper for the Discord REST, WebSocket and Voice APIs. Built on top of [ReactPHP](https://reactphp.org/) components. This documentation is based off the latest `master` branch.

The class reference has moved. You can now access it [here](http://discord-php.github.io/DiscordPHP/reference/).

### Requirements

- PHP 7.3 CLI
    - Will not run on a webserver (FPM, CGI), you must run through CLI.
    - Library _can_ run on PHP 7.2 but support for this version will be removed soon. No support will be given if there are any errors.
    - Expect the requirement to increased to PHP 7.4 without warning.
- `ext-json` for JSON parsing.
- `ext-zlib` for gateway packet compression.

#### Recommended Extensions

- One of `ext-uv` (preferred), `ext-libev` or `evt-event` for a faster, and more performant event loop.
- `ext-mbstring` if you may handle non-english characters.

#### Voice Requirements

- 64-bit Linux or Darwin based OS. Windows is not and is not planned to be supported.
- `ext-sodium` for voice encryption.
- FFmpeg if you plan on playing anything but raw 16-bit little-endian PCM.

### Installation

Installation requries [Composer](https://getcomposer.org).

To install the latest release:

```shell
> composer require team-reflex/discord-php
```

If you would like to run on the latest `master` branch:

```shell
> composer require team-reflex/discord-php dev-master
```

`master` can be substituted for any other branch name to install that branch.

### Key Tips

As Discord is a real-time application, events come frequently and it is vital that your code does not block the ReactPHP event loop.
Most, if not all, functions return promises, therefore it is vital that you understand the concept of asynchronous programming with promises.
You can learn more about ReactPHP promises [here](https://reactphp.org/promise/).

### Help

If you need any help, feel free to join the [PHP Discorders]() Discord and someone should be able to give you a hand. We are a small community so please be patient if someone can't help you straight away.

### Contributing

All contributions are welcome through pull requests in our GitHub repository. At the moment we would love contributions towards:

- Unit testing
- Documentation
