---
title: "User"
---

User represents a user of Discord. The bot can "see" any users that to a guild that they also belong to.

### Properties

| name          | type    | description                                                            |
| ------------- | ------- | ---------------------------------------------------------------------- |
| id            | string  | id of the user                                                         |
| username      | string  | username of the user                                                   |
| discriminator | string  | four-digit discriminator of the user                                   |
| displayname   | string  | username#discriminator                                                 |
| avatar        | string  | avatar URL of the user                                                 |
| avatar_hash   | string  | avatar hash of the user                                                |
| bot           | bool    | whether the user is a bot                                              |
| system        | bool    | whetehr the user is a system user e.g. Clyde                           |
| mfa_enabled   | bool    | whether the user has multifactor authentication enabled                |
| banner        | ?string | the banner URL of the user.                                            |
| banner_hash   | ?string | the banner URL of the user.                                            |
| accent_color  | ?int    | the user's banner color encoded as an integer representation           |
| locale        | ?string | locale of the user                                                     |
| verified      | bool    | whether the user is verified                                           |
| email         | ?string | email of the user                                                      |
| flags         | ?int    | user flags, see the `User` classes constants. use bit masks to compare |
| premium_type  | ?int    | type of nitro, see the `User` classes constants                        |
| public_flags  | ?int    | see flags above                                                        |

### Get private channel for user

Gets the private direct message channel for the user. Returns a [Channel](#channel) in a promise.

```php
$user->getPrivateChannel()->done(function (Channel $channel) {
    // ...
});
```

### Send user a message

Sends a private direct message to the user. Note that your bot account can be suspended for doing this, consult Discord documentation for more information. Returns the message in a promise.

#### Parameters

| name    | type   | description                                   |
| ------- | ------ | --------------------------------------------- |
| message | string | content to send                               |
| tts     | bool   | whether to send the message as text to speech |
| embed   | Embed  | embed to send in the message                  |

```php
$user->sendMessage('Hello, world!', false, $embed)->done(function (Message $message) {
    // ...
});
```

### Get avatar URL

Gets the avatar URL for the user. Only call this function if you need to change the format or size of the image, otherwise use `$user->avatar`. Returns a string.

#### Parameters

| name   | type   | description                                                                   |
| ------ | ------ | ----------------------------------------------------------------------------- |
| format | string | format of the image, one of png, jpg or webp, default webp or gif if animated |
| size   | int    | size of the image, default 1024                                               |

```php
$url = $user->getAvatarAttribute('png', 2048);
echo $url; // https://cdn.discordapp.com/avatars/:user_id/:avatar_hash.png?size=2048
```

### Get banner URL

Gets the banner URL for the user. Only call this function if you need to change the format or size of the image, otherwise use `$user->banner`.
Returns a string or `null` if user has no banner image set.

#### Parameters

| name   | type   | description                                                                  |
| ------ | ------ | ---------------------------------------------------------------------------- |
| format | string | format of the image, one of png, jpg or webp, default png or gif if animated |
| size   | int    | size of the image, default 600                                               |

```php
$url = $user->getBannerAttribute('png', 1024);
echo $url; // https://cdn.discordapp.com/banners/:user_id/:banner_hash.png?size=1024
```
