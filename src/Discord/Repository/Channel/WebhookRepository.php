<?php

/*
 * This file is a part of the DiscordPHP project.
 *
 * Copyright (c) 2015-present David Cole <david.cole1340@gmail.com>
 *
 * This file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Webhook;
use Discord\Repository\AbstractRepository;

/**
 * @inheritdoc
 *
 * @method Webhook|null get(string $discrim, $key)  Gets an item from the collection.
 * @method Webhook|null first()                     Returns the first element of the collection.
 * @method Webhook|null pull($key, $default = null) Pulls an item from the repository, removing and returning the item.
 * @method Webhook|null find(callable $callback)    Runs a filter callback over the repository.
 */
class WebhookRepository extends AbstractRepository
{
    /**
     * @inheritdoc
     */
    protected $endpoints = [
        'all' => Endpoint::CHANNEL_WEBHOOKS,
        'create' => Endpoint::CHANNEL_WEBHOOKS,
        'get' => Endpoint::WEBHOOK,
        'delete' => Endpoint::WEBHOOK,
        'update' => Endpoint::WEBHOOK,
    ];

    /**
     * @inheritdoc
     */
    protected $class = Webhook::class;
}
