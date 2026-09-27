---
title: "Subscriptions"
---

Subscription events arrive when a user subscribes to one of your application's SKUs, when that subscription changes, and, rarely, when Discord deletes one. They are emitted with a `Subscription` object, which is also kept in the `subscriptions` repository of each cached SKU it is for.

### Subscription Create

Called with a `Subscription` object when a user subscribes.

```php
// use Discord\Parts\Monetization\Subscription;

$discord->on(Event::SUBSCRIPTION_CREATE, function (Subscription $subscription, Discord $discord) {
    // ...
});
```

### Subscription Update

Called with a `Subscription` object when a subscription changes, such as when it renews or ends, and with the previous version when it was cached.

```php
$discord->on(Event::SUBSCRIPTION_UPDATE, function (Subscription $subscription, Discord $discord, ?Subscription $old) {
    // ...
});
```

### Subscription Delete

Called with a `Subscription` object when a subscription is deleted. A subscription that ends is updated with its new status rather than deleted.

```php
$discord->on(Event::SUBSCRIPTION_DELETE, function (Subscription $subscription, Discord $discord) {
    // ...
});
```
