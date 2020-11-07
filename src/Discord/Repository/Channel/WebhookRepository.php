<?php

/*
 * This file is apart of the DiscordPHP project.
 *
 * Copyright (c) 2016-2020 David Cole <david.cole1340@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the LICENSE.md file.
 */

namespace Discord\Repository\Channel;

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
        'all' => 'channels/:channel_id/webhooks',
        'create' => 'channels/:channel_id/webhooks',
        'get' => 'webhooks/:id',
        'delete' => 'webhooks/:id',
        'update' => 'webhooks/:id',
    ];

    /**
     * {@inheritdoc}
     */
    protected $class = Webhook::class;
}
