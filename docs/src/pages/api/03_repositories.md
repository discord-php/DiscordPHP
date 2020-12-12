---
title: "Repositories"
---

Repositories are containers for parts. They provide the functions to get, save and delete parts from the Discord servers. Different parts have many repositories.

An example is the `Channel` part. It has 4 repositories: `members`, `messages`, `overwrites` and `webhooks`. Each of these repositories contain parts that relate to the `Channel` part, such as messages sent in the channel (`messages` repository), or if it is a voice channel the members currently in the channel (`members` repository).

A full list of repositories is provided below in the parts section, per part.

Repositories extend the [Collection](#collection) class. See the documentation on collections for extra methods.

Examples provided below are based on the `guilds` repository in the Discord client.

### Methods

All repositories extend the `AbstractRepository` class, and share a set of core methods.

#### Freshening the repository data

Clears the repository and fills it with new data from Discord. It takes no parameters and returns the repository in a promise.

```php
$discord->guilds->freshen()->done(function (GuildRepository $guilds) {
    // ...
});
```

#### Creating a part

Creates a repository part from an array of attributes and returns the part. Does not create the part in Discord servers, you must use the `->save()` function later.

| name       | type  | description                                       |
| ---------- | ----- | ------------------------------------------------- |
| attributes | array | Array of attributes to fill in the part. Optional |

```php
$guild = $discord->guilds->create([
    'name' => 'My new guild name',
]);
// to save
$discord->guilds->save($guild)->done(...);
```

#### Saving a part

Creates or updates a repository part in the Discord servers. Takes a part and returns the same part in a promise.

| name | type | description                  |
| ---- | ---- | ---------------------------- |
| part | Part | The part to create or update |

```php
$discord->guilds->save($guild)->done(function (Guild $guild) {
    // ...
});
```

#### Deleting a part

Deletes a repository part from the Discord servers. Takes a part and returns the old part in a promise.

| name | type | description        |
| ---- | ---- | ------------------ |
| part | Part | The part to delete |

```php
$discord->guilds->delete($guild)->done(function (Guild $guild) {
    // ...
});
```

#### Fetch a part

Fetches/freshens a part from the repository. If the part is present in the cache, it returns the cached version, otherwise it retrieves the part from Discord servers. Takes a part ID and returns the part in a promise.

| name  | type   | description                                                    |
| ----- | ------ | -------------------------------------------------------------- |
| id    | string | Part ID                                                        |
| fresh | bool   | Forces the method to skip checking the cache. Default is false |

```php
$discord->guilds->fetch('guild_id')->done(function (Guild $guild) {
    // ...
});
// or, if you don't want to check the cache
$discord->guilds->fetch('guild_id', true)->done(function (Guild $guild) {
    // ...
});
```
