## Cache

Caching is implemented into DiscordPHP. By default all HTTP `GET` requests are cached for 5 minutes.

If you would like to use the Cache driver in your own applications feel free to do so. All drivers implement `CacheInterface`.

### Examples

```php
use Discord\Cache\Cache;

Cache::set('dank', 'memes', 120); // dank => memes for 120 seconds

Cache::get('dank'); // Returns 'memes'

Cache::has('dank'); // Returns true

Cache::remove('dank'); // Returns true if it succeeded

Cache::clear(); // Deletes all cache objects
```

### Changing Cache time to live

To change how long a request will be stored in the cache, do the following:

```php
use Discord\Helpers\Guzzle;

Guzzle::setCacheTtl(seconds); // Default is 300 seconds (5 minutes).
```

### Disabling the cache

To disable HTTP requests from being cached, do the following:

```php
use Discord\Helpers\Guzzle;

Guzzle::setCacheTtl(0); // Sets the time to live to 0 seconds so it will be deleted.
```

### Cache Drivers

There are multiple cache drivers bundled with DiscordPHP;

- `ArrayCacheDriver` - Stores information in an array. This is used by default if APC is not detected.
- `ApcCacheDriver` - Uses the default PHP APC cache. This is used by default if it is detected.
- `RedisCacheDriver`

To change the driver in use, do the following:

```php
use Discord\Cache\Cache;

// Array
Cache::setCache(new \Discord\Cache\ArrayCacheDriver());

// APC
Cache::setCache(new \Discord\Cache\ApcCacheDriver());

// Redis
Cache::setCache(new \Discord\Cache\RedisCacheDriver(hostname, port, password, databaseID));
```

#### Creating your own Driver

To create your own Cache driver, create a class that implements `Discord\Cache\CacheInterface`. You can find examples under `src/Discord/Cache/Drivers`.

To use your custom driver, do the following:

```php
use Discord\Cache\Cache;

Cache::setCache(new YourOwnDriver(any params you need));
```