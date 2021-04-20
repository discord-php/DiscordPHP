<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2021 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

use Discord\Http\Endpoint;
use Discord\Parts\Channel\Webhook;
use Discord\Repository\AbstractRepository;

/**
 * {@inheritdoc}
 */
class WebhookRepository extends AbstractRepository
{
    /**
     * {@inheritdoc}
     */
    protected $endpoints = [
        'all' => Endpoint::CHANNEL_WEBHOOKS,
        'create' => Endpoint::CHANNEL_WEBHOOKS,
        'get' => Endpoint::WEBHOOK,
        'delete' => Endpoint::WEBHOOK,
        'update' => Endpoint::WEBHOOK,
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Webhook::class;
}
